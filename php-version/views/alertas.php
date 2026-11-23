<?php
declare(strict_types=1);

require __DIR__ . '/layout.php';
exigir_login();

garantir_estrutura_alertas();
$filtroStatus = (string) ($_GET['status'] ?? '');
$filtros = ['status' => in_array($filtroStatus, ['aberto', 'justificado', 'resolvido'], true) ? $filtroStatus : ''];
if (!pode_gerenciar_equipe()) {
    $filtros['responsavel_id'] = (int) usuario_atual()['id'];
}
$alertas = buscar_alertas($filtros);
$usuarioId = (int) usuario_atual()['id'];
$escala = buscar_escala_semanal();
$vigias = pode_gerenciar_equipe() ? vigias_ativos_para_escala() : [];
$rondasConfiguradas = rondas_todas();
$diasSemana = [1 => 'Segunda-feira', 2 => 'Terça-feira', 3 => 'Quarta-feira', 4 => 'Quinta-feira', 5 => 'Sexta-feira', 6 => 'Sábado', 7 => 'Domingo'];

layout_inicio('Alertas de rondas', 'Administrativo');
?>
<?php if (pode_gerenciar_equipe()): ?>
<section class="card escala-semanal">
  <div class="secao-topo">
    <div>
      <p class="progresso-kicker">Responsável pelo turno</p>
      <h2>Escala semanal</h2>
      <p class="muted pequeno">A escala vale para todos os horários ativos. A data do turno define o dia da semana usado no alerta.</p>
    </div>
  </div>
  <?php if (!$vigias): ?>
    <p class="muted">Cadastre pelo menos um usuário com perfil vigia para configurar a escala.</p>
  <?php else: ?>
    <form method="post" action="actions/alerta.php" class="form escala-form">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="acao" value="salvar_escala">
      <div class="escala-grid">
        <?php foreach ($diasSemana as $dia => $rotulo): ?>
          <label>
            <span><?= e($rotulo) ?></span>
            <select name="dia_<?= $dia ?>">
              <option value="">Sem responsável</option>
              <?php foreach ($vigias as $vigia): ?>
                <option value="<?= (int) $vigia['id'] ?>" <?= isset($escala[$dia]) && (int) $escala[$dia]['responsavel_id'] === (int) $vigia['id'] ? 'selected' : '' ?>><?= e($vigia['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primario" type="submit">Salvar escala semanal</button>
    </form>
  <?php endif; ?>
</section>
<?php endif; ?>

<section class="secao-topo alertas-topo">
  <div>
    <h2>Rondas não concluídas</h2>
    <p class="muted pequeno">Alertas gerados após a tolerância configurada, com postos pendentes.</p>
  </div>
  <form method="get" class="alertas-filtro">
    <input type="hidden" name="page" value="alertas">
    <select name="status" onchange="this.form.submit()" aria-label="Filtrar alertas">
      <option value="">Todos</option>
      <option value="aberto" <?= $filtroStatus === 'aberto' ? 'selected' : '' ?>>Abertos</option>
      <option value="justificado" <?= $filtroStatus === 'justificado' ? 'selected' : '' ?>>Justificados</option>
      <option value="resolvido" <?= $filtroStatus === 'resolvido' ? 'selected' : '' ?>>Resolvidos</option>
    </select>
  </form>
</section>

<?php if (!$alertas): ?>
  <div class="vazio">
    <h3>Nenhum alerta encontrado</h3>
    <p class="muted">As rondas completas não geram alertas.</p>
  </div>
<?php else: ?>
  <div class="lista alertas-lista">
    <?php foreach ($alertas as $alerta): ?>
      <?php
        $responsavelId = $alerta['responsavel_id'] ? (int) $alerta['responsavel_id'] : null;
        $podeJustificar = eh_admin() || eh_moderador() || $responsavelId === null || $responsavelId === $usuarioId;
      ?>
      <article class="card alerta <?= $alerta['status'] === 'resolvido' ? 'alerta-resolvido' : '' ?>">
        <div class="alerta-cabecalho">
          <div>
            <p class="progresso-kicker">Ronda <?= e($rondasConfiguradas[(int) $alerta['ronda']]['horario'] ?? ('#' . (int) $alerta['ronda'])) ?></p>
            <h3><?= e($alerta['mensagem']) ?></h3>
          </div>
          <span class="selo <?= $alerta['status'] === 'resolvido' ? 'selo-ok' : ($alerta['status'] === 'justificado' ? 'selo-moderador' : 'selo-alerta') ?>">
            <?= e(status_alerta_rotulo((string) $alerta['status'])) ?>
          </span>
        </div>
        <p class="muted pequeno">
          Turno <?= e(data_br((string) $alerta['turno_data'])) ?> · alerta em <?= e(formatar_hora((string) $alerta['criado_em'])) ?>
          · responsável: <?= e($alerta['responsavel_nome'] ?: 'não identificado') ?>
        </p>

        <div class="alerta-pendentes">
          <strong><?= (int) $alerta['quantidade_pendente'] ?> posto(s) pendente(s)</strong>
          <ul>
            <?php foreach ($alerta['postos_pendentes_lista'] as $postoNome): ?>
              <li><?= e((string) $postoNome) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <?php if (!empty($alerta['justificativa'])): ?>
          <div class="alerta-justificativa">
            <strong>Justificativa</strong>
            <p><?= nl2br(e((string) $alerta['justificativa'])) ?></p>
            <span class="muted pequeno">Registrada por <?= e($alerta['justificado_nome'] ?: 'usuário') ?></span>
          </div>
        <?php endif; ?>

        <?php if ($podeJustificar && $alerta['status'] !== 'resolvido'): ?>
          <details class="alerta-formulario">
            <summary><?= $alerta['justificativa'] ? 'Atualizar justificativa' : 'Registrar justificativa' ?></summary>
            <form method="post" action="actions/alerta.php" class="form">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $alerta['id'] ?>">
              <input type="hidden" name="acao" value="justificar">
              <textarea name="justificativa" rows="3" maxlength="2000" required placeholder="Explique o motivo do atraso ou da pendência."><?= e((string) ($alerta['justificativa'] ?? '')) ?></textarea>
              <button class="btn btn-secundario" type="submit">Salvar justificativa</button>
            </form>
          </details>
        <?php endif; ?>

        <?php if (pode_gerenciar_equipe() && $alerta['status'] !== 'resolvido'): ?>
          <form method="post" action="actions/alerta.php" class="alerta-resolver" onsubmit="return confirm('Marcar este alerta como resolvido?');">
            <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) $alerta['id'] ?>">
            <input type="hidden" name="acao" value="resolver">
            <button class="link-discreto" type="submit">Marcar como resolvido</button>
          </form>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php layout_fim('alertas'); ?>
