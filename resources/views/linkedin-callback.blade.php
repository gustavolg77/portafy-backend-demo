<!DOCTYPE html>
<html>
<body>
@php
  $payload = isset($error)
    ? ['type' => 'LINKEDIN_AUTH_ERROR', 'error' => $error]
    : ['type' => 'LINKEDIN_AUTH_SUCCESS', 'token' => $token, 'user' => $user];
@endphp
<script>
  const payload = @json($payload);
  const frontendUrl = String(@json(config('app.frontend_url')) || "").replace(/\/+$/, "");
  const frontendOrigin = frontendUrl || "";
  const callbackUrl = frontendUrl
    ? `${frontendUrl}/auth/popup-callback#payload=${encodeURIComponent(JSON.stringify(payload))}`
    : "";

  try {
    if (window.opener && frontendOrigin) {
      try {
        window.opener.postMessage(payload, frontendOrigin);
      } catch {}
    }

    if (callbackUrl) {
      window.location.replace(callbackUrl);
    }
  } finally {
    if (!callbackUrl) {
      window.setTimeout(() => {
        window.close();
      }, 150);
    }
  }
</script>
</body>
</html>
