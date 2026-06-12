<?php
// activites.php - Calendrier des autres activités
// Projet Old Dating - BUT1 Informatique

require_once '../includes/session.php';
require_once '../config.php';
require_once '../fonctions_besoin1.php';
require_once '../fonctions_besoin2.php';

requireLogin();

$user      = getAdherentSession();
$initiales = getInitiales($user['prenom'] ?? 'U', $user['nom'] ?? '');

// Navigation mois/année
$mois  = isset($_GET['mois'])  ? (int)$_GET['mois']  : (int)date('m');
$annee = isset($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');

// Normalisation
if ($mois < 1)  { $mois = 12; $annee--; }
if ($mois > 12) { $mois = 1;  $annee++; }

// Filtre catégorie
$categorie = isset($_GET['cat']) && $_GET['cat'] !== '' ? $_GET['cat'] : null;

// Événement sélectionné
$selectedId = isset($_GET['ev']) ? (int)$_GET['ev'] : null;

// Traitement inscription
$msgInscription = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrire'])) {
    $idEv   = (int)$_POST['id_evenement'];
    $result = inscrireAdherent($user['id_adherent'], $idEv);
    $msgInscription = $result['message'];
    $typeMsg = $result['succes'] ? 'success' : 'error';
    if ($result['succes']) $selectedId = $idEv;
}

// Données
$categories = getCategoriesActivites();
$activites  = getActivitesDuMois($mois, $annee, $categorie);
$selectedEv = $selectedId ? getEvenementParId($selectedId) : ($activites[0] ?? null);
$dejaInscrit = $selectedEv ? estDejaInscrit($user['id_adherent'], $selectedEv['id_evenement']) : false;

// Index des activités par jour
$evParJour = [];
foreach ($activites as $ev) {
    $jour = (int) date('j', strtotime($ev['date_evenement']));
    $evParJour[$jour][] = $ev;
}

// Calcul calendrier
$premierJour = mktime(0,0,0,$mois,1,$annee);
$nbJours     = (int) date('t', $premierJour);
$jourSemaine = (int) date('N', $premierJour); // 1=Lun, 7=Dim

$moisFr = ['','Janvier','Février','Mars','Avril','Mai','Juin',
           'Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$moisPrev  = $mois - 1 ?: 12;
$anneePrev = $mois - 1 ? $annee : $annee - 1;
$moisSuiv  = $mois + 1 > 12 ? 1 : $mois + 1;
$anneeSuiv = $mois + 1 > 12 ? $annee + 1 : $annee;

$today   = (int)date('j');
$moisAuj = (int)date('m');
$anneeAuj = (int)date('Y');

// Couleurs de badge par catégorie (cycle)
$badgeClasses = ['badge-teal','badge-orange','badge-green','badge-gray'];
$catIndex = [];
foreach ($categories as $i => $cat) {
    $catIndex[$cat] = $badgeClasses[$i % count($badgeClasses)];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Activités</title>
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
      <a href="dashboard.php"  class="nav-item"><span class="nav-icon">🏠</span> Accueil</a>
      <a href="calendrier.php" class="nav-item"><span class="nav-icon">📅</span> Calendrier</a>
      <a href="activites.php"  class="nav-item active"><span class="nav-icon">🎵</span> Activités</a>
      <a href="agenda.php"     class="nav-item"><span class="nav-icon">📖</span> Mon agenda</a>
      <a href="chat.php"       class="nav-item"><span class="nav-icon">💬</span> Messages</a>
      <a href="profil.php"     class="nav-item"><span class="nav-icon">👤</span> Mon profil</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../logout.php" class="nav-item" style="color:var(--red)">
        <span class="nav-icon">🚪</span> Déconnexion
      </a>
    </div>
  </aside>

  <main class="main-content" id="contenu-principal">

    <div class="page-header">
      <div class="breadcrumb"><a href="dashboard.php">Accueil</a> › Autres activités</div>
      <div class="page-title">🎵 Autres activités</div>
    </div>

    <?php if ($msgInscription): ?>
      <div class="alert alert-<?= $typeMsg ?? 'info' ?>">
        <?= $typeMsg === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($msgInscription) ?>
      </div>
    <?php endif; ?>

    <div class="flex mb-24" style="flex-wrap:wrap;gap:8px;align-items:center">
      <a href="?mois=<?= $mois ?>&annee=<?= $annee ?>"
         class="btn btn-sm <?= $categorie === null ? 'btn-primary' : 'btn-outline' ?>">
        🎵 Tous
      </a>
      <?php foreach ($categories as $cat): ?>
        <a href="?mois=<?= $mois ?>&annee=<?= $annee ?>&cat=<?= urlencode($cat) ?>"
           class="btn btn-sm <?= $categorie === $cat ? 'btn-primary' : 'btn-outline' ?>">
          <?= htmlspecialchars($cat) ?>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="cal-layout">

      <div>
        <div class="cal-nav">
          <a href="?mois=<?= $moisPrev ?>&annee=<?= $anneePrev ?><?= $categorie ? '&cat='.urlencode($categorie) : '' ?>"
             style="text-decoration:none">
            <button type="button">‹</button>
          </a>
          <h3><?= $moisFr[$mois] ?> <?= $annee ?></h3>
          <a href="?mois=<?= $moisSuiv ?>&annee=<?= $anneeSuiv ?><?= $categorie ? '&cat='.urlencode($categorie) : '' ?>"
             style="text-decoration:none">
            <button type="button">›</button>
          </a>
        </div>

        <div class="cal-grid">
          <div class="cal-days-header">
            <?php foreach (['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'] as $j): ?>
              <div><?= $j ?></div>
            <?php endforeach; ?>
          </div>
          <div class="cal-body">
            <?php
            for ($i = 1; $i < $jourSemaine; $i++): ?>
              <div class="cal-cell other-month"></div>
            <?php endfor;

            for ($j = 1; $j <= $nbJours; $j++):
              $isToday    = ($j === $today && $mois === $moisAuj && $annee === $anneeAuj);
              $hasEv      = isset($evParJour[$j]);
              $firstEvId  = $hasEv ? $evParJour[$j][0]['id_evenement'] : null;
              $isSelected = $selectedEv && $hasEv &&
                            in_array($selectedEv['id_evenement'], array_column($evParJour[$j], 'id_evenement'));
            ?>
              <a href="?mois=<?= $mois ?>&annee=<?= $annee ?>&ev=<?= $firstEvId ?? '' ?><?= $categorie ? '&cat='.urlencode($categorie) : '' ?>"
                 style="text-decoration:none;color:inherit"
                 class="cal-cell <?= $isToday ? 'today' : '' ?> <?= $isSelected ? 'selected' : '' ?>">
                <div class="cal-day"><?= $j ?></div>
                <?php if ($hasEv): ?>
                  <div class="cal-dots">
                    <div class="cal-dot teal"></div>
                  </div>
                <?php endif; ?>
              </a>
            <?php endfor; ?>
          </div>
        </div>

        <div style="margin-top:24px">
          <div class="section-title">
            Toutes les activités de <?= $moisFr[$mois] ?>
            <?php if ($categorie): ?>
              — <span style="font-weight:400;text-transform:none"><?= htmlspecialchars($categorie) ?></span>
            <?php endif; ?>
          </div>

          <?php if (empty($activites)): ?>
            <div class="card" style="text-align:center;color:var(--text-muted);padding:24px">
              Aucune activité ce mois-ci<?= $categorie ? ' pour cette catégorie' : '' ?>.
            </div>
          <?php else: ?>
            <?php foreach ($activites as $ev):
              $complet   = $ev['nb_inscrits'] >= $ev['capacite_max'];
              $inscrit   = estDejaInscrit($user['id_adherent'], $ev['id_evenement']);
              $dateTs    = strtotime($ev['date_evenement']);
              $jours     = ['','Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];
              $jourLabel = $jours[(int)date('N', $dateTs)];
              $badgeCls  = isset($ev['categorie'], $catIndex[$ev['categorie']]) ? $catIndex[$ev['categorie']] : 'badge-gray';
            ?>
              <div class="event-card <?= $complet ? '' : 'orange-border' ?>"
                   style="<?= $complet ? 'opacity:.6' : '' ?>">
                <div class="event-date-pill" style="<?= $complet ? 'background:var(--bg)' : '' ?>">
                  <div class="day"><?= date('d', $dateTs) ?></div>
                  <div class="month"><?= strtoupper($jourLabel) ?></div>
                </div>
                <div class="event-info" style="flex:1;min-width:0">
                  <?php if (!empty($ev['categorie'])): ?>
                    <span class="badge <?= $badgeCls ?>" style="margin-bottom:4px">
                      <?= htmlspecialchars($ev['categorie']) ?>
                    </span>
                  <?php endif; ?>
                  <div class="event-title"><?= htmlspecialchars($ev['titre']) ?></div>
                  <div class="event-meta">
                    <span>🕑 <?= substr($ev['heure_debut'],0,5) ?></span>
                    <span>📍 <?= htmlspecialchars($ev['lieu']) ?></span>
                    <span>👥 <?= $ev['nb_inscrits'] ?>/<?= $ev['capacite_max'] ?></span>
                  </div>
                </div>
                <?php if ($complet): ?>
                  <span class="badge badge-gray">Complet 🔒</span>
                <?php elseif ($inscrit): ?>
                  <span class="badge badge-green">Inscrit ✓</span>
                <?php else: ?>
                  <a href="inscription.php?id=<?= $ev['id_evenement'] ?>" class="btn btn-primary btn-sm">
                    S'inscrire
                  </a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <?php if ($selectedEv): ?>
          <?php
            $dateTs  = strtotime($selectedEv['date_evenement']);
            $jFr     = ['','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
            $jLabel  = $jFr[(int)date('N', $dateTs)];
            $complet = $selectedEv['nb_inscrits'] >= $selectedEv['capacite_max'];
            $pct     = $selectedEv['capacite_max'] > 0
                       ? round($selectedEv['nb_inscrits'] / $selectedEv['capacite_max'] * 100)
                       : 0;
            $badgeCls = isset($selectedEv['categorie'], $catIndex[$selectedEv['categorie']])
                        ? $catIndex[$selectedEv['categorie']] : 'badge-teal';
          ?>

          <div style="color:var(--orange);font-weight:700;font-size:1.05rem;margin-bottom:12px">
            <?= $jLabel ?> <?= date('j', $dateTs) ?> <?= $moisFr[$mois] ?> <?= $annee ?>
          </div>

          <div class="card" style="border-left:4px solid var(--teal)">
            <div class="mb-16" style="flex-wrap:wrap;display:flex;gap:8px">
              <?php if (!empty($selectedEv['categorie'])): ?>
                <span class="badge <?= $badgeCls ?>"><?= htmlspecialchars($selectedEv['categorie']) ?></span>
              <?php endif; ?>
              <?php if ($complet): ?>
                <span class="badge badge-gray">Complet</span>
              <?php elseif ($dejaInscrit): ?>
                <span class="badge badge-green">✓ Inscrit(e)</span>
              <?php endif; ?>
            </div>

            <h3 style="font-size:1.2rem;margin-bottom:14px">
              <?= htmlspecialchars($selectedEv['titre']) ?>
            </h3>

            <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:18px;font-size:.9rem">
              <div>🕑 <?= substr($selectedEv['heure_debut'],0,5) ?>
                <?php if (!empty($selectedEv['heure_fin'])): ?>
                  – <?= substr($selectedEv['heure_fin'],0,5) ?>
                <?php endif; ?>
              </div>
              <div>📍 <?= htmlspecialchars($selectedEv['lieu']) ?></div>
              <div>👥 <?= $selectedEv['nb_inscrits'] ?> participants inscrits sur <?= $selectedEv['capacite_max'] ?> places</div>
            </div>

            <div class="progress-bar">
              <div class="progress-fill <?= $pct >= 80 ? 'orange' : '' ?>"
                   style="width:<?= $pct ?>%"></div>
            </div>
            <div class="text-xs text-muted" style="margin-bottom:18px"><?= $pct ?>% rempli</div>

            <?php if (!empty($selectedEv['description'])): ?>
              <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:16px">
                <?= htmlspecialchars($selectedEv['description']) ?>
              </p>
            <?php endif; ?>

            <?php if ($complet): ?>
              <button class="btn btn-ghost btn-full" disabled>Activité complète 🔒</button>
            <?php elseif ($dejaInscrit): ?>
              <div class="alert alert-success">✅ Vous êtes inscrit(e) à cette activité !</div>
            <?php else: ?>
              <a href="inscription.php?id=<?= $selectedEv['id_evenement'] ?>"
                 class="btn btn-primary btn-full btn-lg">
                Je m'inscris à cette activité →
              </a>
            <?php endif; ?>
          </div>

        <?php else: ?>
          <div class="card" style="text-align:center;color:var(--text-muted);padding:40px 24px">
            <span style="font-size:2.5rem;display:block;margin-bottom:12px">🎵</span>
            Sélectionnez un jour dans le calendrier pour voir le détail d'une activité.
          </div>
        <?php endif; ?>
      </div>

    </div>
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
