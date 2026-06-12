<?php
// dashboard.php - Tableau de bord de l'adhérent
// Projet Old Dating - BUT1 Informatique

require_once '../includes/session.php';
require_once '../config.php';
require_once '../fonctions_besoin1.php';
require_once '../fonctions_besoin2.php';

requireLogin();

$user        = getAdherentSession();
$initiales   = getInitiales($user['prenom'] ?? 'U', $user['nom'] ?? '');
$moisCourant = (int) date('m');
$anneeCourante = (int) date('Y');

// Prochains événements inscrits
$inscriptions = getInscriptionsAdherent($user['id_adherent']);
// Garder seulement les 2 prochains à venir
$inscriptionsAVenir = array_filter($inscriptions, fn($i) => $i['date_evenement'] >= date('Y-m-d'));
$inscriptionsAVenir = array_slice(array_values($inscriptionsAVenir), 0, 2);

$moisFr = ['','Janvier','Février','Mars','Avril','Mai','Juin',
           'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

function formatDateFr(string $date): string {
    $moisFr = ['','jan.','fév.','mars','avr.','mai','juin',
               'juil.','août','sept.','oct.','nov.','déc.'];
    $ts  = strtotime($date);
    $jour = date('d', $ts);
    $mois = $moisFr[(int) date('m', $ts)];
    return "$jour $mois";
}

function jourSemaineFr(string $date): string {
    $jours = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
    return $jours[(int) date('w', strtotime($date))];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Accueil</title>
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
        <div class="user-sub"><?= htmlspecialchars($user['pseudo']) ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"   class="nav-item active"><span class="nav-icon">🏠</span> Accueil</a>
      <a href="calendrier.php"  class="nav-item"><span class="nav-icon">📅</span> Calendrier</a>
      <a href="activites.php"   class="nav-item"><span class="nav-icon">🎵</span> Activités</a>
      <a href="agenda.php"      class="nav-item"><span class="nav-icon">📖</span> Mon agenda</a>
      <a href="chat.php"        class="nav-item"><span class="nav-icon">💬</span> Messages</a>
      <a href="profil.php"      class="nav-item"><span class="nav-icon">👤</span> Mon profil</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../logout.php" class="nav-item" style="color:var(--red)">
        <span class="nav-icon">🚪</span> Déconnexion
      </a>
    </div>
  </aside>

  <main class="main-content" id="contenu-principal">

    <div class="banner banner-orange flex-between mb-24" style="flex-wrap:wrap;gap:12px">
      <div>
        <h2>Bonjour <?= htmlspecialchars($user['prenom']) ?> !</h2>
        <p>
          <?php if (count($inscriptionsAVenir) > 0): ?>
            Vous avez <?= count($inscriptionsAVenir) ?> événement(s) à venir cette semaine.
          <?php else: ?>
            Bienvenue sur Old Dating. Découvrez les événements du mois !
          <?php endif; ?>
        </p>
      </div>
      <a href="calendrier.php" class="btn btn-ghost" style="background:rgba(255,255,255,.2);color:#fff;border-color:rgba(255,255,255,.4)">
        Voir mon planning →
      </a>
    </div>

    <div class="grid-2 mb-28" style="margin-bottom:28px">

      <a href="calendrier.php" class="card" style="border-left:4px solid var(--teal);text-decoration:none;color:inherit;transition:transform .15s;display:block" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <div class="flex-between mb-8">
          <span style="font-size:1.6rem">📅</span>
          <span class="badge badge-teal">3 événements ce mois</span>
        </div>
        <div class="font-bold" style="font-size:1.05rem;margin-bottom:4px">Calendrier des rencontres</div>
        <div class="text-muted text-sm">Voir les après-midis de rencontre à venir</div>
      </a>

      <a href="activites.php" class="card" style="border-left:4px solid var(--teal);text-decoration:none;color:inherit;transition:transform .15s;display:block" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <div class="flex-between mb-8">
          <span style="font-size:1.6rem">🎵</span>
          <span class="badge badge-orange">2 nouveautés</span>
        </div>
        <div class="font-bold" style="font-size:1.05rem;margin-bottom:4px">Autres activités</div>
        <div class="text-muted text-sm">Chants, danses, jeux de cartes et plus</div>
      </a>

      <a href="agenda.php" class="card" style="border-left:4px solid var(--teal);text-decoration:none;color:inherit;transition:transform .15s;display:block" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <div class="flex-between mb-8">
          <span style="font-size:1.6rem">📖</span>
          <span class="badge badge-green">1 anniversaire demain 🎂</span>
        </div>
        <div class="font-bold" style="font-size:1.05rem;margin-bottom:4px">Mon agenda</div>
        <div class="text-muted text-sm">Vos rendez-vous et anniversaires importants</div>
      </a>

      <a href="chat.php" class="card" style="border-left:4px solid var(--orange);text-decoration:none;color:inherit;transition:transform .15s;display:block;background:var(--orange-light)" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform=''">
        <div class="flex-between mb-8">
          <span style="font-size:1.6rem">💬</span>
          <span class="badge badge-orange">2 messages non lus</span>
        </div>
        <div class="font-bold" style="font-size:1.05rem;margin-bottom:4px">Chat &amp; Messages</div>
        <div class="text-muted text-sm">Discutez avec vos amis seniors</div>
      </a>

    </div>

    <div class="section-title">📅 Vos prochains événements
      <a href="calendrier.php" style="margin-left:auto;font-size:.82rem;font-weight:400">Tout voir →</a>
    </div>

    <?php if (empty($inscriptionsAVenir)): ?>
      <div class="card" style="text-align:center;padding:32px;color:var(--muted)">
        <span style="font-size:2rem;display:block;margin-bottom:8px">📭</span>
        Vous n'avez pas encore d'événements à venir.
        <a href="calendrier.php" style="display:block;margin-top:10px">Parcourir le calendrier →</a>
      </div>
    <?php else: ?>
      <?php foreach ($inscriptionsAVenir as $ev): ?>
        <div class="event-card orange-border">
          <div class="event-date-pill">
            <div class="day"><?= date('d', strtotime($ev['date_evenement'])) ?></div>
            <div class="month"><?= strtoupper(substr(formatDateFr($ev['date_evenement']), 3)) ?></div>
          </div>
          <div class="event-info">
            <div class="event-title"><?= htmlspecialchars($ev['titre']) ?></div>
            <div class="event-meta">
              <span>🕑 <?= substr($ev['heure_debut'], 0, 5) ?></span>
              <span>📍 <?= htmlspecialchars($ev['lieu']) ?></span>
            </div>
          </div>
          <span class="badge badge-green">Inscrit ✓</span>
        </div>
      <?php endforeach; ?>
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
