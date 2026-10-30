{{-- Poçt proqramları xarici CSS oxumur — üslub sətir daxilindədir. --}}
<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Yol Yoldaşı</title>
</head>
<body style="margin:0;padding:0;background:#F3F6FA;font-family:Arial,Helvetica,sans-serif;color:#081226">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3F6FA;padding:32px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:16px;padding:32px">
        <tr><td>
          <p style="margin:0 0 24px;font-size:20px;font-weight:bold">Yol <span style="color:#00B389">Yoldaşı</span></p>

          <p style="margin:0 0 12px;font-size:16px">Salam{{ $name ? ', '.$name : '' }}!</p>

          <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#3A4A63">
            @if ($purpose === 'reset')
              Şifrəni yeniləmək üçün bu kodu saytda yaz:
            @else
              Qeydiyyatı tamamlamaq üçün bu kodu saytda yaz:
            @endif
          </p>

          <p style="margin:0 0 20px;font-size:34px;font-weight:bold;letter-spacing:8px;text-align:center;background:#E8FAF4;color:#06694F;border-radius:12px;padding:16px 0">{{ $code }}</p>

          <p style="margin:0 0 8px;font-size:14px;color:#5B6B85">Kod {{ $minutes }} dəqiqə etibarlıdır.</p>
          <p style="margin:0;font-size:14px;color:#5B6B85">Bu sorğunu sən göndərməmisənsə, məktubu nəzərə alma — heç nə dəyişməyəcək.</p>
        </td></tr>
      </table>
      <p style="margin:16px 0 0;font-size:12px;color:#8A97AB">yolyoldasi.az</p>
    </td></tr>
  </table>
</body>
</html>
