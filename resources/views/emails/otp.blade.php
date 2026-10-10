<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kode verifikasi</title>
</head>
<body style="margin:0;padding:0;background:#f1f4f0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;color:#17231f;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="padding:32px 16px;">
    <tr><td align="center">
      <table role="presentation" width="100%" style="max-width:480px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #dce3de;">
        <tr><td style="background:#1f4d43;padding:20px 28px;">
          <span style="color:#ffffff;font-size:18px;font-weight:700;">Perpustakaan Digital</span>
        </td></tr>
        <tr><td style="padding:28px;">
          <p style="margin:0 0 12px;font-size:15px;">Halo{{ $name ? ' ' . $name : '' }},</p>
          <p style="margin:0 0 20px;font-size:15px;color:#47564f;">{{ $intro }}</p>
          <div style="text-align:center;margin:28px 0;">
            <span style="display:inline-block;font-size:34px;font-weight:700;letter-spacing:8px;color:#1f4d43;background:#e2efe9;padding:14px 28px;border-radius:10px;">{{ $code }}</span>
          </div>
          <p style="margin:0 0 8px;font-size:13px;color:#74827b;">Kode berlaku {{ $ttlMinutes }} menit. Jangan bagikan kode ini kepada siapa pun, termasuk yang mengaku sebagai petugas perpustakaan.</p>
          <p style="margin:20px 0 0;font-size:13px;color:#74827b;">Jika Anda tidak merasa meminta kode ini, abaikan saja email ini.</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
