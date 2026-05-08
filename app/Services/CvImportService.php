<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CvImportService
{
    private const GROQ_ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';
    private const GROQ_MODEL    = 'llama-3.3-70b-versatile';

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const PDF_MIME    = 'application/pdf';
    private const TEXT_MIME   = 'text/plain';
    private const DOCX_MIMES  = [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/msword',
    ];

    // ──────────────────────────────────────────────────────────────────────────
    //  Punto de entrada público
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Recibe el archivo subido y devuelve un array estructurado con los datos
     * del CV listos para pre-rellenar los formularios del frontend.
     */
    public function import(UploadedFile $file): array
    {
        $mime = $file->getMimeType();

        // Imágenes → por ahora no soportadas (Groq no tiene visión)
        if (in_array($mime, self::IMAGE_MIMES, true)) {
            throw new RuntimeException(
                'Las imágenes no están soportadas actualmente. '
                . 'Sube tu CV como PDF, Word (.docx) o TXT.'
            );
        }

        // PDF, DOCX, TXT → extraer texto y enviar a Groq
        $text = match (true) {
            $mime === self::PDF_MIME                 => $this->extractFromPdf($file),
            in_array($mime, self::DOCX_MIMES, true) => $this->extractFromDocx($file),
            $mime === self::TEXT_MIME                => file_get_contents($file->getRealPath()),
            default => throw new RuntimeException(
                "Tipo de archivo no soportado ({$mime}). "
                . "Sube un PDF, Word (.docx) o TXT."
            ),
        };

        if (empty(trim($text))) {
            throw new RuntimeException(
                'No se pudo extraer texto del archivo. '
                . 'Verifica que el PDF no esté protegido o escaneado como imagen.'
            );
        }

        return $this->parseWithGroq($text);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Extracción de texto
    // ──────────────────────────────────────────────────────────────────────────

    private function extractFromPdf(UploadedFile $file): string
    {
        $parser = new \Smalot\PdfParser\Parser();
        $pdf    = $parser->parseFile($file->getRealPath());
        return $pdf->getText();
    }

    private function extractFromDocx(UploadedFile $file): string
    {
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getRealPath());
        $text    = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $text .= $child->getText() . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        return $text;
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Llamada a Groq
    // ──────────────────────────────────────────────────────────────────────────

    private function parseWithGroq(string $text): array
    {
        $apiKey = config('services.groq.key');

        if (empty($apiKey)) {
            throw new RuntimeException('GROQ_API_KEY no está configurada en el servidor.');
        }

        $prompt = $this->buildPrompt() . "\n\nCV:\n" . mb_substr($text, 0, 12000);

        $response = Http::timeout(30)
            ->withToken($apiKey)
            ->post(self::GROQ_ENDPOINT, [
                'model'       => self::GROQ_MODEL,
                'temperature' => 0.1,
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Error al conectar con Groq: ' . $response->status()
                . ' — ' . $response->body()
            );
        }

        $raw = $response->json('choices.0.message.content', '');

        return $this->decodeJson($raw);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Prompt + decodificación
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Prompt que instruye al LLM a devolver SOLO JSON estructurado.
     *
     * Los enums deben coincidir exactamente con la BD:
     *   habilidades.tipo            → 'tecnica' | 'blanda'
     *   habilidades.nivel_texto     → 'basico' | 'intermedio' | 'avanzado' | 'experto'
     *   formaciones.nivel_formacion → 'tecnico' | 'tecnologo' | 'licenciatura' | 'ingenieria'
     *                                 | 'maestria' | 'doctorado' | 'curso' | 'diplomado' | 'otro'
     *   redes_sociales.plataforma   → 'linkedin' | 'github' | 'gitlab' | 'facebook'
     *                                 | 'instagram' | 'x' | 'youtube' | 'portafolio' | 'otro'
     */
    private function buildPrompt(): string
    {
        return <<<'PROMPT'
Eres un extractor de datos de currículums vitae (CV). Tu única tarea es analizar el CV
proporcionado y devolver ÚNICAMENTE un objeto JSON válido, sin texto adicional, sin
explicaciones, sin bloques de código markdown (no uses ```json).

El JSON debe seguir EXACTAMENTE esta estructura (omite campos que no encuentres,
nunca los inventes):

{
  "nombre": "string",
  "apellido": "string",
  "profesion": "string (título profesional o cargo actual)",
  "biografia": "string (resumen profesional si existe, máx 300 chars)",
  "ubicacion": "string (ciudad, país)",
  "fecha_nacimiento": "YYYY-MM-DD o null",
  "habilidades": [
    {
      "nombre": "string",
      "tipo": "tecnica | blanda",
      "nivel_texto": "basico | intermedio | avanzado | experto | null"
    }
  ],
  "experiencias": [
    {
      "empresa": "string",
      "cargo": "string",
      "descripcion": "string o null",
      "fecha_inicio": "YYYY-MM-DD",
      "fecha_fin": "YYYY-MM-DD o null",
      "actualmente": true | false
    }
  ],
  "formaciones": [
    {
      "institucion": "string",
      "nombre_programa": "string",
      "nivel_formacion": "tecnico | tecnologo | licenciatura | ingenieria | maestria | doctorado | curso | diplomado | otro",
      "fecha_inicio": "YYYY-MM-DD o null",
      "fecha_fin": "YYYY-MM-DD o null",
      "actualmente": true | false
    }
  ],
  "sociales": [
    {
      "plataforma": "linkedin | github | gitlab | facebook | instagram | x | youtube | portafolio | otro",
      "url": "string"
    }
  ]
}
PROMPT;
    }

    /**
     * Limpia la respuesta del LLM y la decodifica como JSON.
     * Los modelos a veces envuelven el JSON en bloques ```json ... ``` aunque se les pida que no.
     */
    private function decodeJson(string $raw): array
    {
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($raw));
        $clean = preg_replace('/\s*```$/', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'El servicio de IA devolvió una respuesta inválida. '
                . 'Intenta de nuevo o usa un archivo diferente.'
            );
        }

        return $decoded;
    }
}
