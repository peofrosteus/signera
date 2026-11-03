<!doctype html>
<html lang="sv">
<head><meta charset="utf-8"><title><?= e($subject) ?></title></head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#1f2933;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 0;">
  <tr><td align="center">
    <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:8px;">
      <tr><td style="padding:28px 32px;font-size:15px;line-height:1.55;">
        <?= $body ?>
      </td></tr>
    </table>
    <p style="font-size:12px;color:#7b8794;margin:16px 0 0;"><?= e(\Signera\App::config('app_name', 'Signera')) ?> · <?= e(parse_url((string) \Signera\App::config('base_url'), PHP_URL_HOST)) ?></p>
  </td></tr>
</table>
</body>
</html>
