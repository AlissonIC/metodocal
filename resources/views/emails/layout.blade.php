<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $titulo ?? config('variables.templateName') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; color:#2a2e34; line-height:1.6;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f4f6f9; padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.05);">

          {{-- Header com gradiente da marca --}}
          <tr>
            <td style="background: linear-gradient(135deg, #007da8 0%, #09d2e8 100%); padding:32px 32px 24px; text-align:center;">
              <h1 style="margin:0; color:#ffffff; font-size:26px; font-weight:700; letter-spacing:-0.5px;">
                {{ config('variables.templateName') }}
              </h1>
              @isset($subtitulo)
                <p style="margin:8px 0 0; color:rgba(255,255,255,0.92); font-size:14px;">{{ $subtitulo }}</p>
              @endisset
            </td>
          </tr>

          {{-- Conteúdo --}}
          <tr>
            <td style="padding:32px;">
              @if(isset($saudacao))
                <p style="margin:0 0 16px; font-size:16px;">{{ $saudacao }}</p>
              @endif

              {{ $slot ?? '' }}
              @yield('content')

              @isset($botao_texto)
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;">
                  <tr>
                    <td align="center" bgcolor="#007da8" style="background: linear-gradient(135deg, #007da8 0%, #09d2e8 100%); border-radius:6px;">
                      <a href="{{ $botao_url }}"
                         style="display:inline-block; padding:12px 28px; color:#ffffff; text-decoration:none; font-weight:600; font-size:15px;">
                        {{ $botao_texto }}
                      </a>
                    </td>
                  </tr>
                </table>
                <p style="margin:16px 0 0; font-size:13px; color:#6c757d;">
                  Se o botão acima não funcionar, copie e cole este endereço no navegador:<br>
                  <span style="word-break:break-all; color:#007da8;">{{ $botao_url }}</span>
                </p>
              @endisset
            </td>
          </tr>

          {{-- Footer --}}
          <tr>
            <td style="background-color:#f8f9fa; padding:24px 32px; text-align:center; font-size:12px; color:#6c757d;">
              <p style="margin:0 0 6px;">© {{ date('Y') }} {{ config('variables.templateName') }}. Todos os direitos reservados.</p>
              <p style="margin:0;">Esta é uma mensagem automática — por favor, não responda este e-mail.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
