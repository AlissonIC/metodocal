{{-- ==================== MODAIS LEGAIS ==================== --}}
{{-- Termos de uso + Política de Privacidade/LGPD.
     Inclua este partial uma vez por página (landing, footer do dashboard, etc).
     Triggers: data-bs-toggle="modal" data-bs-target="#modalTermos" / "#modalLgpd"
--}}

<div class="modal fade" id="modalTermos" tabindex="-1" aria-labelledby="modalTermosLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalTermosLabel">Termos de uso — {{ config('variables.templateName', 'MetodoCal') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">Última atualização: junho de 2026</p>

        <h6>1. Sobre o serviço</h6>
        <p>O {{ config('variables.templateName', 'MetodoCal') }} é uma plataforma digital de mentoria e licenciamento que oferece conteúdos educacionais, materiais didáticos, sessões de acompanhamento e ferramentas integradas (agenda, CRM, gestão de comissões e processos auxiliares) voltadas a pessoas interessadas em adquirir veículos a preços de oportunidade, com segurança jurídica.</p>
        <p>Todo o conteúdo tem caráter <strong>estritamente educacional e informativo</strong>. O serviço não comercializa veículos, não intermedia leilões nem garante qualquer resultado financeiro decorrente da aplicação do método.</p>

        <h6>2. Aceitação</h6>
        <p>Ao criar uma conta, contratar um plano ou utilizar qualquer recurso da plataforma, o usuário declara ter lido, compreendido e aceitado integralmente estes Termos e a Política de Privacidade.</p>

        <h6>3. Cadastro e conta</h6>
        <p>O usuário é responsável pela veracidade dos dados informados no cadastro e pela guarda de suas credenciais de acesso. É vedado compartilhar login ou senha com terceiros. Atividade detectada como abusiva pode resultar em suspensão da conta sem aviso prévio.</p>

        <h6>4. Planos, pagamentos e renovação</h6>
        <p>As assinaturas são processadas via Mercado Pago e podem ser mensais, trimestrais, semestrais ou anuais, conforme o plano contratado. Os pagamentos são recorrentes e a cobrança ocorre automaticamente no início de cada novo ciclo, salvo cancelamento prévio.</p>
        <p>Em caso de falha no pagamento, o acesso aos recursos pagos pode ser suspenso até a regularização. O reembolso segue o disposto no Código de Defesa do Consumidor (Lei nº 8.078/1990): direito de arrependimento de 7 dias corridos a contar da contratação, para contratações realizadas fora do estabelecimento físico.</p>

        <h6>5. Cancelamento</h6>
        <p>O cancelamento pode ser solicitado a qualquer momento pelo painel do usuário ou por e-mail. O acesso permanece ativo até o fim do ciclo já pago, sem novas cobranças.</p>

        <h6>6. Propriedade intelectual</h6>
        <p>Todos os conteúdos, materiais, marcas, layout, código-fonte, vídeos, textos e ferramentas disponibilizados são de propriedade exclusiva do {{ config('variables.templateName', 'MetodoCal') }} ou licenciados a ele. É vedada qualquer forma de reprodução, redistribuição, gravação ou comercialização sem autorização expressa.</p>

        <h6>7. Uso permitido</h6>
        <p>O usuário se compromete a utilizar a plataforma apenas para fins lícitos, sem prejudicar terceiros, sem tentar acesso indevido a áreas restritas, sem realizar engenharia reversa e sem utilizar bots, scrapers ou ferramentas automatizadas não autorizadas.</p>

        <h6>8. Limitação de responsabilidade</h6>
        <p>O {{ config('variables.templateName', 'MetodoCal') }} não se responsabiliza por decisões de compra, prejuízos financeiros, vícios em veículos negociados pelo usuário ou eventuais litígios decorrentes da aplicação do método. A responsabilidade pela diligência prévia (vistoria, laudo, situação jurídica do bem) é integralmente do usuário.</p>

        <h6>9. Alterações</h6>
        <p>Estes Termos podem ser atualizados a qualquer momento para refletir mudanças legais ou operacionais. Alterações relevantes serão comunicadas pelo e-mail cadastrado e/ou pelo painel do usuário.</p>

        <h6>10. Foro</h6>
        <p>Fica eleito o foro da Comarca da Capital do Estado do Rio de Janeiro/RJ para dirimir quaisquer questões oriundas destes Termos, com renúncia a qualquer outro, por mais privilegiado que seja.</p>

        <h6>11. Contato</h6>
        <p>Dúvidas, solicitações ou notificações relacionadas a estes Termos podem ser enviadas para <a href="mailto:contato@metodocal.com.br">contato@metodocal.com.br</a>.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalLgpd" tabindex="-1" aria-labelledby="modalLgpdLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalLgpdLabel">Política de Privacidade · LGPD — {{ config('variables.templateName', 'MetodoCal') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3">Última atualização: junho de 2026</p>

        <p>Esta Política descreve como o {{ config('variables.templateName', 'MetodoCal') }} ("nós") coleta, utiliza, armazena e protege dados pessoais, em conformidade com a <strong>Lei Geral de Proteção de Dados — LGPD (Lei nº 13.709/2018)</strong>.</p>

        <h6>1. Controlador dos dados</h6>
        <p>O controlador dos dados pessoais é o {{ config('variables.templateName', 'MetodoCal') }}, com canal oficial de contato pelo e-mail <a href="mailto:contato@metodocal.com.br">contato@metodocal.com.br</a>.</p>

        <h6>2. Dados que coletamos</h6>
        <ul>
          <li><strong>Cadastrais:</strong> nome, CPF/CNPJ, e-mail, telefone, data de nascimento.</li>
          <li><strong>De pagamento:</strong> processados diretamente pelo Mercado Pago. <em>Não armazenamos números de cartão de crédito.</em></li>
          <li><strong>De uso:</strong> registros de acesso, IP, dispositivo, navegador, páginas visitadas e ações dentro da plataforma.</li>
          <li><strong>De interação:</strong> mensagens, comentários, anotações em sessões de mentoria, materiais baixados.</li>
        </ul>

        <h6>3. Finalidades do tratamento</h6>
        <ul>
          <li>Permitir o cadastro, autenticação e acesso aos recursos contratados.</li>
          <li>Processar pagamentos, emitir notas e gerenciar assinaturas.</li>
          <li>Enviar comunicações operacionais (boas-vindas, cobrança, atualizações de plano) e, com consentimento, comunicações de marketing.</li>
          <li>Personalizar a experiência e recomendar conteúdos pertinentes.</li>
          <li>Cumprir obrigações legais e regulatórias.</li>
          <li>Prevenir fraudes e proteger a segurança da plataforma e dos usuários.</li>
        </ul>

        <h6>4. Base legal</h6>
        <p>O tratamento se ampara em: <strong>execução de contrato</strong> (art. 7º, V), <strong>cumprimento de obrigação legal</strong> (art. 7º, II), <strong>legítimo interesse</strong> (art. 7º, IX) e <strong>consentimento</strong> (art. 7º, I) — este último para comunicações de marketing e cookies não essenciais.</p>

        <h6>5. Compartilhamento</h6>
        <p>Dados pessoais podem ser compartilhados com:</p>
        <ul>
          <li><strong>Mercado Pago</strong> — para processamento de pagamentos.</li>
          <li><strong>Provedores de infraestrutura</strong> — hospedagem, e-mail transacional, analytics.</li>
          <li><strong>Autoridades</strong> — quando exigido por lei ou ordem judicial.</li>
        </ul>
        <p>Não vendemos nem cedemos dados pessoais a terceiros para fins comerciais.</p>

        <h6>6. Cookies</h6>
        <p>Utilizamos cookies essenciais para o funcionamento da plataforma (sessão, preferências de idioma, autenticação) e, mediante consentimento, cookies analíticos e de marketing. O usuário pode gerenciar cookies pelas configurações do navegador.</p>

        <h6>7. Direitos do titular (LGPD, art. 18)</h6>
        <p>O titular pode, a qualquer tempo, solicitar:</p>
        <ul>
          <li>Confirmação da existência de tratamento;</li>
          <li>Acesso aos dados;</li>
          <li>Correção de dados incompletos, inexatos ou desatualizados;</li>
          <li>Anonimização, bloqueio ou eliminação de dados desnecessários ou tratados em desconformidade com a LGPD;</li>
          <li>Portabilidade;</li>
          <li>Eliminação dos dados tratados com base no consentimento;</li>
          <li>Informação sobre compartilhamento;</li>
          <li>Revogação do consentimento.</li>
        </ul>
        <p>As solicitações devem ser enviadas pelo canal de contato indicado no item 1 e serão respondidas no prazo legal.</p>

        <h6>8. Retenção</h6>
        <p>Os dados são mantidos enquanto a conta estiver ativa e pelo prazo necessário ao cumprimento de obrigações legais, fiscais e contratuais. Após esse período, são anonimizados ou eliminados de forma segura.</p>

        <h6>9. Segurança</h6>
        <p>Adotamos medidas técnicas e organizacionais para proteger os dados contra acesso não autorizado, perda, alteração ou destruição: criptografia em trânsito (HTTPS), controle de acesso, registros de auditoria e backups regulares. Nenhum sistema é 100% imune; em caso de incidente, comunicaremos os titulares e a ANPD nos termos da lei.</p>

        <h6>10. Encarregado (DPO)</h6>
        <p>Para questões específicas sobre privacidade e proteção de dados, o usuário pode contatar o Encarregado pelo e-mail <a href="mailto:contato@metodocal.com.br">contato@metodocal.com.br</a>.</p>

        <h6>11. Alterações</h6>
        <p>Esta Política pode ser atualizada periodicamente. A versão vigente sempre estará disponível neste mesmo link, com indicação da data da última atualização.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
      </div>
    </div>
  </div>
</div>
