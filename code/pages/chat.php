<?php
// chat.php - Chat entre adhérents
// Projet Old Dating - BUT1 Informatique

require_once '../includes/session.php';
require_once '../config.php';
require_once '../fonctions_besoin1.php';
require_once '../fonctions_besoin3_4.php';

requireLogin();

$user      = getAdherentSession();
$initiales = getInitiales($user['prenom'] ?? 'U', $user['nom'] ?? '');

// Conversation active
$idConvActive = isset($_GET['conv']) ? (int)$_GET['conv'] : null;

// Données chargées avant le POST pour la vérification d'appartenance
$conversations = getConversationsAdherent($user['id_adherent']);

// Vérifie que l'adhérent est bien participant de la conversation
$idConvsAutorisees = array_column($conversations, 'id_conversation');
$estParticipant = fn(int $idConv): bool => in_array($idConv, $idConvsAutorisees, true);

// Traitement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idConv = (int)($_POST['id_conversation'] ?? 0);

    if (isset($_POST['envoyer_message']) && !empty(trim($_POST['contenu'] ?? ''))) {
        if (!$estParticipant($idConv)) { http_response_code(403); exit; }
        envoyerMessage($idConv, $user['id_adherent'], trim($_POST['contenu']), 'normal');
        header("Location: chat.php?conv=$idConv");
        exit;
    }

    if (isset($_POST['envoyer_festif'])) {
        if (!$estParticipant($idConv)) { http_response_code(403); exit; }
        $type = $_POST['type_festif'] ?? 'anniversaire';
        envoyerMessageFestif($idConv, $user['id_adherent'], $type);
        header("Location: chat.php?conv=$idConv");
        exit;
    }

    if (isset($_POST['changer_statut'])) {
        mettreAJourStatutChat($user['id_adherent'], $_POST['statut_chat']);
        $_SESSION['adherent']['statut_chat'] = $_POST['statut_chat'];
        $user = getAdherentSession();
    }
}

if (!$idConvActive && !empty($conversations)) {
    $idConvActive = $conversations[0]['id_conversation'];
}

$messages = $idConvActive ? getMessagesConversation($idConvActive) : [];

if ($idConvActive && $estParticipant($idConvActive)) {
    marquerMessagesLus($idConvActive, $user['id_adherent']);
}

// Trouver l'interlocuteur de la conversation active
$interlocuteur = null;
foreach ($conversations as $conv) {
    if ($conv['id_conversation'] == $idConvActive) {
        $interlocuteur = [
            'id'     => $conv['id_interlocuteur'],
            'pseudo' => $conv['pseudo_interlocuteur'],
            'avatar' => $conv['avatar_interlocuteur'],
            'statut' => $conv['statut_interlocuteur'],
        ];
        break;
    }
}

$monStatut = $user['statut_chat'] ?? 'en_ligne';

$statutLabels = [
    'en_ligne'        => '🟢 En ligne',
    'absent'          => '🟠 Absent',
    'ne_pas_deranger' => '⚫ Ne pas déranger',
];

$moisFr = ['','janvier','février','mars','avril','mai','juin',
           'juillet','août','septembre','octobre','novembre','décembre'];
$joursFrLong = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];

function statutDotClass(string $statut): string {
    return match($statut) {
        'en_ligne'        => 'online',
        'absent'          => 'absent',
        'ne_pas_deranger' => 'dnd',
        default           => 'absent',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Messages</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<a href="#contenu-principal" class="skip-link">Aller au contenu principal</a>
<button type="button" class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Ouvrir le menu">☰</button>
<div class="mobile-overlay" id="mobile-overlay" onclick="toggleMobileMenu()"></div>
<div class="app-wrapper">

  <aside class="sidebar">
    <div class="sidebar-logo">
      <div class="logo-name">🤝 Old Dating</div>
      <div class="logo-sub">Rencontres &amp; Activités</div>
    </div>
    <div class="sidebar-user">
      <div class="avatar avatar-40"><?= $initiales ?></div>
      <div>
        <div class="user-greeting">Bonjour, <?= htmlspecialchars($user['prenom']) ?> !</div>
        <div class="user-sub"><?= htmlspecialchars($user['pseudo'] ?? '') ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-item"><span class="nav-icon">🏠</span> Accueil</a>
      <a href="calendrier.php" class="nav-item"><span class="nav-icon">📅</span> Calendrier</a>
      <a href="activites.php"  class="nav-item"><span class="nav-icon">🎵</span> Activités</a>
      <a href="agenda.php"     class="nav-item"><span class="nav-icon">📖</span> Mon agenda</a>
      <a href="chat.php"       class="nav-item active"><span class="nav-icon">💬</span> Messages</a>
      <a href="profil.php"     class="nav-item"><span class="nav-icon">👤</span> Mon profil</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../logout.php" class="nav-item" style="color:var(--red)">
        <span class="nav-icon">🚪</span> Déconnexion
      </a>
    </div>
  </aside>

  <main class="main-content" id="contenu-principal" style="padding:0;display:flex;flex-direction:column;overflow:hidden">

    <div class="chat-layout">

      <div class="chat-contacts">

        <div class="chat-contacts-header">
          <h3 style="margin-bottom:10px">Mes conversations</h3>
          <input type="text" placeholder="🔍 Rechercher un membre…"
                 style="padding:9px 14px;font-size:.88rem">
          <div style="display:flex;gap:6px;margin-top:10px">
            <span class="btn btn-primary btn-sm" style="padding:5px 12px;font-size:.78rem">Tous</span>
            <span class="btn btn-outline btn-sm" style="padding:5px 12px;font-size:.78rem">En ligne</span>
            <span class="btn btn-outline btn-sm" style="padding:5px 12px;font-size:.78rem">Non lus</span>
          </div>
        </div>

        <?php if (empty($conversations)): ?>
          <div style="padding:24px 16px;text-align:center;color:var(--text-muted);font-size:.9rem">
            Aucune conversation pour l'instant.
          </div>
        <?php else: ?>
          <?php foreach ($conversations as $conv): ?>
            <a href="chat.php?conv=<?= $conv['id_conversation'] ?>"
               class="contact-item <?= $conv['id_conversation'] == $idConvActive ? 'active' : '' ?>"
               style="text-decoration:none;color:inherit">
              <div class="avatar-wrap">
                <div class="avatar avatar-48">
                  <?= getInitiales($conv['pseudo_interlocuteur']) ?>
                </div>
                <span class="status-dot <?= statutDotClass($conv['statut_interlocuteur']) ?>"></span>
              </div>
              <div class="contact-info" style="flex:1;min-width:0">
                <div class="contact-name"><?= htmlspecialchars($conv['pseudo_interlocuteur']) ?></div>
                <div class="contact-preview">
                  <?= htmlspecialchars(mb_strimwidth($conv['dernier_message'] ?? '', 0, 35, '…')) ?>
                </div>
              </div>
              <div style="text-align:right;flex-shrink:0">
                <div class="contact-time">
                  <?= $conv['date_dernier_message'] ? date('H:i', strtotime($conv['date_dernier_message'])) : '' ?>
                </div>
                <?php if (($conv['nb_non_lus'] ?? 0) > 0): ?>
                  <span class="badge badge-orange" style="margin-top:4px;font-size:.7rem">
                    <?= $conv['nb_non_lus'] ?>
                  </span>
                <?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>

        <div style="padding:12px 16px;border-top:1px solid var(--border);margin-top:auto">
          <form method="POST" style="display:flex;align-items:center;gap:8px">
            <input type="hidden" name="id_conversation" value="<?= $idConvActive ?>">
            <span class="text-xs text-muted">Mon statut :</span>
            <select name="statut_chat" onchange="this.form.submit()"
                    style="padding:5px 10px;font-size:.82rem;border-radius:20px;border:1px solid var(--border);background:var(--bg);cursor:pointer">
              <option value="en_ligne"        <?= $monStatut === 'en_ligne'        ? 'selected' : '' ?>>🟢 En ligne</option>
              <option value="absent"          <?= $monStatut === 'absent'          ? 'selected' : '' ?>>🟠 Absent</option>
              <option value="ne_pas_deranger" <?= $monStatut === 'ne_pas_deranger' ? 'selected' : '' ?>>⚫ Ne pas déranger</option>
            </select>
            <input type="hidden" name="changer_statut" value="1">
          </form>
        </div>

      </div>

      <div class="chat-window">

        <?php if (!$interlocuteur): ?>
          <div style="flex:1;display:flex;align-items:center;justify-content:center;flex-direction:column;color:var(--text-muted)">
            <span style="font-size:3rem;margin-bottom:12px">💬</span>
            <p>Sélectionnez une conversation pour commencer à discuter.</p>
          </div>

        <?php else: ?>

          <div class="chat-header">
            <div class="avatar-wrap">
              <div class="avatar avatar-48"><?= getInitiales($interlocuteur['pseudo']) ?></div>
              <span class="status-dot <?= statutDotClass($interlocuteur['statut']) ?>"></span>
            </div>
            <div>
              <div class="chat-name"><?= htmlspecialchars($interlocuteur['pseudo']) ?></div>
              <div class="chat-status"><?= htmlspecialchars($statutLabels[$interlocuteur['statut']] ?? 'Absent') ?></div>
            </div>
          </div>

          <div class="messages-area">
            <?php if (empty($messages)): ?>
              <div style="text-align:center;color:var(--text-muted);padding:40px">
                <span style="font-size:2rem;display:block;margin-bottom:8px">👋</span>
                Commencez la conversation avec <?= htmlspecialchars($interlocuteur['pseudo']) ?> !
              </div>
            <?php else: ?>
              <?php
              $lastDate = '';
              foreach ($messages as $msg):
                $isMine  = ($msg['id_expediteur'] == $user['id_adherent']);
                $dateMsg = date('Y-m-d', strtotime($msg['date_envoi']));
                $heureMsg = date('H:i', strtotime($msg['date_envoi']));

                if ($dateMsg !== $lastDate):
                  $lastDate = $dateMsg;
                  $ts       = strtotime($dateMsg);
                  $dateFr   = $joursFrLong[(int)date('w',$ts)] . ' ' . date('j',$ts) . ' '
                              . $moisFr[(int)date('m',$ts)] . ' ' . date('Y',$ts);
                  if ($dateMsg === date('Y-m-d')) {
                      $dateFr = "Aujourd'hui, " . date('j',$ts) . ' ' . $moisFr[(int)date('m',$ts)] . ' ' . date('Y',$ts);
                  }
              ?>
                <div class="date-sep"><?= $dateFr ?></div>
              <?php endif; ?>

              <div class="message-row <?= $isMine ? 'mine' : '' ?>">
                <?php if (!$isMine): ?>
                  <div class="avatar avatar-40" style="font-size:.85rem;flex-shrink:0">
                    <?= getInitiales($msg['pseudo_expediteur']) ?>
                  </div>
                <?php endif; ?>
                <div>
                  <div class="bubble <?= $isMine ? 'mine' : 'other' ?>">
                    <?php if (($msg['type_message'] ?? 'normal') !== 'normal'): ?>
                      <div style="font-size:.75rem;opacity:.75;margin-bottom:4px">
                        <?= $msg['type_message'] === 'anniversaire' ? '🎂 Message d\'anniversaire' : '🎄 Message de fêtes' ?>
                      </div>
                    <?php endif; ?>
                    <?= htmlspecialchars($msg['contenu']) ?>
                  </div>
                  <div class="msg-time">
                    <?= $heureMsg ?><?= $isMine ? ' ✓✓' : '' ?>
                  </div>
                </div>
              </div>

              <?php endforeach; ?>
            <?php endif; ?>
          </div>

          <div class="quick-messages">
            <span style="font-size:.88rem;color:var(--orange-dark);font-weight:600">🎉 Envoyer un message spécial :</span>
            <form method="POST" style="display:inline">
              <input type="hidden" name="id_conversation" value="<?= $idConvActive ?>">
              <input type="hidden" name="type_festif" value="anniversaire">
              <button type="submit" name="envoyer_festif" class="quick-pill">🎂 Joyeux anniversaire !</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="id_conversation" value="<?= $idConvActive ?>">
              <input type="hidden" name="type_festif" value="fetes">
              <button type="submit" name="envoyer_festif" class="quick-pill">🎄 Bonnes fêtes !</button>
            </form>
          </div>

          <?php if (($interlocuteur['statut'] ?? '') === 'ne_pas_deranger'): ?>
            <div class="alert alert-warn" style="margin:0;border-radius:0;border-left:none;border-right:none">
              ⚠️ <?= htmlspecialchars($interlocuteur['pseudo']) ?> ne souhaite pas être dérangé(e) pour le moment.
            </div>
          <?php endif; ?>

          <div class="chat-input-area">
            <span style="font-size:1.5rem;cursor:pointer" title="Emoji">😊</span>
            <form method="POST" style="flex:1;display:flex;gap:10px">
              <input type="hidden" name="id_conversation" value="<?= $idConvActive ?>">
              <input type="text" name="contenu" placeholder="Votre message…"
                     autocomplete="off" required
                     style="border-radius:50px"
                     <?= ($interlocuteur['statut'] ?? '') === 'ne_pas_deranger' ? 'disabled' : '' ?>>
              <button type="submit" name="envoyer_message" class="btn btn-primary"
                      style="white-space:nowrap"
                      <?= ($interlocuteur['statut'] ?? '') === 'ne_pas_deranger' ? 'disabled' : '' ?>>
                Envoyer →
              </button>
            </form>
          </div>
          <div style="text-align:center;padding:4px 0;font-size:.75rem;color:var(--text-muted)">
            💬 Soyez bienveillant(e) et respectueux(se) dans vos échanges.
          </div>

        <?php endif; ?>

      </div>

    </div>

  </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const area = document.querySelector('.messages-area');
    if (area) area.scrollTop = area.scrollHeight;
});
function toggleMobileMenu() {
  document.querySelector('.sidebar').classList.toggle('mobile-open');
  document.getElementById('mobile-overlay').classList.toggle('active');
}
</script>
</body>
</html>
