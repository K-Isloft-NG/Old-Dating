<?php
require_once '../includes/session.php';
require_once '../config.php';
require_once '../fonctions_besoin1.php';
require_once '../fonctions_besoin2.php';

requireLogin();
$user      = getAdherentSession();
$initiales = getInitiales($user['prenom'] ?? 'U', $user['nom'] ?? '');

$idEv = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idEv) { header('Location: calendrier.php'); exit; }

$evenement = getEvenementParId($idEv);
if (!$evenement) { header('Location: calendrier.php'); exit; }

$dejaInscrit = estDejaInscrit($user['id_adherent'], $idEv);
$complet     = $evenement['nb_inscrits'] >= $evenement['capacite_max'];

$step   = 1;
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmer'])) {
    if (!$dejaInscrit && !$complet) {
        $result = inscrireAdherent($user['id_adherent'], $idEv);
        $succes = $result['succes'];
        if ($succes) { $step = 3; $dejaInscrit = true; }
    }
}

$moisFr = ['','Janvier','Février','Mars','Avril','Mai','Juin',
           'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$jFr    = ['','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
$dateTs = strtotime($evenement['date_evenement']);
$pct    = round($evenement['nb_inscrits'] / max(1,$evenement['capacite_max']) * 100);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Inscription</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<a href="#contenu-principal" class="skip-link">Aller au contenu principal</a>
<button type="button" class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Ouvrir le menu">☰</button>
<div class="mobile-overlay" id="mobile-overlay" onclick="toggleMobileMenu()"></div>
<div class="app-wrapper">

  <aside class="sidebar">
    <div class="sidebar-logo"><div class="logo-name">🤝 Old Dating</div><div class="logo-sub">Rencontres &amp; Activités</div></div>
    <div class="sidebar-user">
      <div class="avatar avatar-40"><?= $initiales ?></div>
      <div><div class="user-greeting">Bonjour, <?= htmlspecialchars($user['prenom']) ?> !</div><div class="user-sub"><?= htmlspecialchars($user['pseudo']) ?></div></div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"  class="nav-item"><span class="nav-icon">🏠</span> Accueil</a>
      <a href="calendrier.php" class="nav-item active"><span class="nav-icon">📅</span> Calendrier</a>
      <a href="activites.php"  class="nav-item"><span class="nav-icon">🎵</span> Activités</a>
      <a href="agenda.php"     class="nav-item"><span class="nav-icon">📖</span> Mon agenda</a>
      <a href="chat.php"       class="nav-item"><span class="nav-icon">💬</span> Messages</a>
    </nav>
    <div class="sidebar-bottom"><a href="../logout.php" class="nav-item" style="color:var(--red)"><span class="nav-icon">🚪</span> Déconnexion</a></div>
  </aside>

  <main class="main-content" id="contenu-principal">
    <div class="page-header">
      <div class="breadcrumb"><a href="dashboard.php">Accueil</a> › <a href="calendrier.php">Calendrier</a> › Inscription</div>
      <div class="page-title">✅ Inscription à un événement</div>
    </div>

    <?php if ($succes): ?>
      <div class="alert alert-success">✅ Inscription confirmée ! Un SMS a été envoyé au <?= htmlspecialchars(substr($user['telephone'] ?? '06••••••42', 0, 2).'••••••'.substr($user['telephone'] ?? '00', -2)) ?></div>
    <?php endif; ?>

    <div class="banner banner-teal mb-24">
      <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:center">
        <div>
          <div style="font-size:.85rem;opacity:.8;margin-bottom:4px">📅 <?= $jFr[(int)date('N',$dateTs)] ?> <?= date('j',$dateTs) ?> <?= $moisFr[(int)date('m',$dateTs)] ?> <?= date('Y',$dateTs) ?></div>
          <h2 style="font-size:1.25rem;margin-bottom:6px"><?= htmlspecialchars($evenement['titre']) ?></h2>
          <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:.88rem;opacity:.9">
            <span>🕑 <?= substr($evenement['heure_debut'],0,5) ?><?= $evenement['heure_fin'] ? ' – '.substr($evenement['heure_fin'],0,5) : '' ?></span>
            <span>📍 <?= htmlspecialchars($evenement['lieu']) ?></span>
          </div>
        </div>
        <div style="background:rgba(255,255,255,.2);border-radius:var(--radius-sm);padding:10px 16px;text-align:center;min-width:130px">
          <div style="font-size:1.2rem;font-weight:700"><?= $evenement['nb_inscrits'] ?>/<?= $evenement['capacite_max'] ?></div>
          <div style="font-size:.78rem;opacity:.85"><?= $evenement['capacite_max'] - $evenement['nb_inscrits'] ?> places restantes</div>
        </div>
      </div>
    </div>

    <div class="stepper mb-24">
      <div class="step <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'done' : '' ?>">
        <div class="step-circle"><?= $step > 1 ? '✓' : '1' ?></div>
        <div class="step-label">Vos informations</div>
      </div>
      <div class="step-line <?= $step > 1 ? 'done' : '' ?>"></div>
      <div class="step <?= $step >= 2 ? 'active' : '' ?> <?= $step > 2 ? 'done' : '' ?>">
        <div class="step-circle"><?= $step > 2 ? '✓' : '2' ?></div>
        <div class="step-label">Confirmation</div>
      </div>
      <div class="step-line <?= $step > 2 ? 'done' : '' ?>"></div>
      <div class="step <?= $step >= 3 ? 'done' : '' ?>">
        <div class="step-circle"><?= $step >= 3 ? '✓' : '3' ?></div>
        <div class="step-label">Récapitulatif</div>
      </div>
    </div>

    <?php if ($dejaInscrit && !$succes): ?>
      <div class="alert alert-info">ℹ️ Vous êtes déjà inscrit(e) à cet événement.</div>
      <a href="calendrier.php" class="btn btn-teal">← Retour au calendrier</a>

    <?php elseif ($complet): ?>
      <div class="alert alert-error">❌ Cet événement est complet.</div>
      <a href="calendrier.php" class="btn btn-teal">← Retour au calendrier</a>

    <?php elseif ($step < 3): ?>
      <div class="card" style="border-top:4px solid var(--orange);max-width:640px">
        <p style="margin-bottom:20px;color:var(--muted)">
          Bonjour <?= htmlspecialchars($user['prenom']) ?> ! Vérifiez vos informations avant de confirmer.
        </p>

        <form method="POST">
          <div class="grid-2" style="margin-bottom:20px">
            <div class="form-group" style="margin-bottom:0">
              <label>Votre nom complet 🔒</label>
              <input type="text" value="<?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?>" class="locked" readonly>
            </div>
            <div class="form-group" style="margin-bottom:0">
              <label>Votre numéro de téléphone 🔒</label>
              <input type="text" value="<?= htmlspecialchars(substr($user['telephone']??'06',0,2).' •• •• •• '.substr($user['telephone']??'00',-2)) ?>" class="locked" readonly>
              <div class="form-hint">Un SMS de confirmation sera envoyé à ce numéro.<br>
                <a href="#">Ce n'est pas votre numéro ? Contactez l'administrateur</a>
              </div>
            </div>
          </div>

          <div style="background:var(--teal-light);border-radius:var(--radius-sm);padding:14px 16px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px">
            <input type="checkbox" id="engagement" name="engagement" required
                   style="width:22px;height:22px;margin-top:2px;accent-color:var(--teal);flex-shrink:0">
            <label for="engagement" style="font-weight:400;cursor:pointer;margin-bottom:0">
              Je confirme ma participation et je m'engage à prévenir en cas d'empêchement.
            </label>
          </div>

          <div class="form-group">
            <label>Un message pour l'organisateur ? <span class="text-muted">(facultatif)</span></label>
            <textarea name="message_organisateur" rows="3" placeholder="Ex : J'ai besoin d'une place assise près de l'entrée…" maxlength="200"></textarea>
            <div class="form-hint">200 caractères max.</div>
          </div>

          <div class="alert alert-info" style="margin-bottom:16px">
            📱 <em>C'est simple et rapide !</em> Vous pouvez annuler à tout moment.
          </div>

          <input type="hidden" name="confirmer" value="1">
          <button type="submit" class="btn btn-primary btn-full btn-lg">
            Confirmer mon inscription →
          </button>
        </form>
      </div>

    <?php else: ?>
      <div class="card" style="border-left:4px solid var(--green);max-width:640px">
        <div class="alert alert-success mb-16">✅ Inscrit(e) avec succès !</div>
        <h3 style="margin-bottom:12px"><?= htmlspecialchars($evenement['titre']) ?></h3>
        <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:24px;font-size:.92rem">
          <div>📅 <?= $jFr[(int)date('N',$dateTs)] ?> <?= date('j',$dateTs) ?> <?= $moisFr[(int)date('m',$dateTs)] ?> <?= date('Y',$dateTs) ?></div>
          <div>🕑 <?= substr($evenement['heure_debut'],0,5) ?></div>
          <div>📍 <?= htmlspecialchars($evenement['lieu']) ?></div>
          <div>📱 SMS envoyé au <?= htmlspecialchars(substr($user['telephone']??'06',0,2).'••••••'.substr($user['telephone']??'00',-2)) ?></div>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <a href="agenda.php"     class="btn btn-outline">Voir dans mon agenda</a>
          <a href="calendrier.php" class="btn btn-primary">Retour au calendrier</a>
        </div>
      </div>
    <?php endif; ?>

  </main>
</div>
<script>
function toggleMobileMenu() {
  document.querySelector('.sidebar').classList.toggle('mobile-open');
  document.getElementById('mobile-overlay').classList.toggle('active');
}
</script>
</body>
</html>
