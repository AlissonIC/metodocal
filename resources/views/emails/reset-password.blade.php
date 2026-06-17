@extends('emails.layout', [
  'titulo' => 'Redefinir senha',
  'subtitulo' => 'Solicitação de nova senha',
  'saudacao' => 'Olá ' . ($user->name ?? '') . ',',
  'botao_texto' => 'Redefinir minha senha',
  'botao_url' => $resetUrl,
])

@section('content')
  <p style="margin:0 0 16px; font-size:15px;">
    Recebemos uma solicitação para redefinir a senha da sua conta no
    <strong>{{ config('variables.templateName') }}</strong>.
  </p>
  <p style="margin:0 0 8px; font-size:15px;">
    Clique no botão abaixo para criar uma nova senha. O link é válido por
    <strong>{{ $minutosValidade }} minutos</strong>.
  </p>

  @php /* botão renderizado automaticamente pelo layout */ @endphp

  <hr style="border:none; border-top:1px solid #e9ecef; margin:24px 0;">
  <p style="margin:0; font-size:13px; color:#6c757d;">
    Se você não solicitou a redefinição da sua senha, ignore esta mensagem — sua conta continua segura.
  </p>
@endsection
