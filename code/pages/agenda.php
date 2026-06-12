<?php
// agenda.php - Agenda personnel de l'adhérent
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

// Traitement ajout d'une entrée personnelle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_entree'])) {
    ajouterEntreeAgenda(
        $user['id_adherent'],
        trim($_POST['titre']),
        $_POST['date_entree'],
        !empty($_POST['heure'])  ? $_POST['heure']        : null,
        !empty($_POST['note'])   ? trim($_POST['note'])    : null,
        $_POST['type_entree'] ?? 'autre'
    );
    header("Location: agenda.php?mois=$mois&annee=$annee&ajout=1");
    exit;
}

$msgAjout = isset($_GET['ajout']) ? 'Entrée ajoutée à votre agenda !' : '';

// Données
$agenda = getAgendaAdherentDuMois($user['id_adherent'], $mois, $annee);

// Index pour les dots du calendrier
$dotsParJour = [];
foreach ($agenda as $entry) {
    $jour = (int) date('j', strtotime($entry['date_item']));
    if (($entry['source'] ?? '') === 'inscription') {
        $dotsParJour[$jour][] = 'orange';
    } elseif (($entry['type_entree'] ?? '') === 'anniversaire') {
        $dotsParJour[$jour][] = 'green';
    } else {
        $dotsParJour[$jour][] = 'blue';
    }
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

$today    = (int)date('j');
$moisAuj  = (int)date('m');
$anneeAuj = (int)date('Y');

$joursFr = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
$joursFrLong = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Mon agenda</title>
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
      <a href="activites.php"  class="nav-item"><span class="nav-icon">🎵</span> Activités</a>
      <a href="agenda.php"     class="nav-item active"><span class="nav-icon">📖</span> Mon agenda</a>
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

    <div class="page-header flex-between mb-24">
      <div>
        <div class="breadcrumb"><a href="dashboard.php">Accueil</a> › Mon agenda</div>
        <div class="page-title">📖 Mon agenda</div>
      </div>
      <div class="flex" style="gap:8px">
        <span class="btn btn-primary btn-sm">Vue mensuelle</span>
        <span class="btn btn-outline btn-sm">Vue liste</span>
      </div>
    </div>

    <?php if ($msgAjout): ?>
      <div class="alert alert-success">✅ <?= htmlspecialchars($msgAjout) ?></div>
    <?php endif; ?>

    <div class="cal-layout">

      <div>
        <div class="cal-nav">
          <a href="?mois=<?= $moisPrev ?>&annee=<?= $anneePrev ?>" style="text-decoration:none">
            <button type="button">‹</button>
          </a>
          <h3><?= $moisFr[$mois] ?> <?= $annee ?></h3>
          <a href="?mois=<?= $moisSuiv ?>&annee=<?= $anneeSuiv ?>" style="text-decoration:none">
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
              $isToday = ($j === $today && $mois === $moisAuj && $annee === $anneeAuj);
              $hasDots = isset($dotsParJour[$j]);
            ?>
              <div class="cal-cell <?= $isToday ? 'today' : '' ?>">
                <div class="cal-day"><?= $j ?></div>
                <?php if ($hasDots): ?>
                  <div class="cal-dots">
                    <?php foreach (array_unique($dotsParJour[$j]) as $dotColor): ?>
                      <?php if ($dotColor === 'green'): ?>
                        <div class="cal-dot" style="background:#2E7D32"></div>
                      <?php elseif ($dotColor === 'blue'): ?>
                        <div class="cal-dot" style="background:#1565C0"></div>
                      <?php else: ?>
                        <div class="cal-dot orange"></div>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endfor; ?>
          </div>
        </div>

        <div style="margin-top:16px;padding:16px;background:var(--white);border-radius:var(--radius-md);border:1px solid var(--border)">
          <div style="font-size:.85rem;font-weight:600;margin-bottom:10px;text-transform:uppercase;color:var(--text-muted)">Légende</div>
          <div style="display:flex;flex-direction:column;gap:6px;font-size:.88rem">
            <div style="display:flex;align-items:center;gap:8px"><span class="cal-dot orange" style="width:10px;height:10px"></span> Mes inscriptions</div>
            <div style="display:flex;align-items:center;gap:8px"><span class="cal-dot" style="width:10px;height:10px;background:#2E7D32"></span> Anniversaires</div>
            <div style="display:flex;align-items:center;gap:8px"><span class="cal-dot" style="width:10px;height:10px;background:#1565C0"></span> Dates importantes</div>
          </div>
        </div>
      </div>

      <div>
        <div class="flex-between mb-16">
          <div class="section-title"><?= $moisFr[$mois] ?> <?= $annee ?> en un coup d'œil</div>
          <button onclick="document.getElementById('form-ajout').style.display = document.getElementById('form-ajout').style.display === 'none' ? 'block' : 'none'"
                  class="btn btn-outline btn-sm">＋ Ajouter un rappel</button>
        </div>

        <?php if (empty($agenda)): ?>
          <div class="card" style="text-align:center;color:var(--text-muted);padding:40px 24px">
            <span style="font-size:2.5rem;display:block;margin-bottom:12px">📭</span>
            Aucun événement ce mois-ci.<br>Parcourez le calendrier pour vous inscrire !
          </div>
        <?php else: ?>
          <?php foreach ($agenda as $entry):
            $dateTs   = strtotime($entry['date_item']);
            $jourIdx  = (int)date('w', $dateTs);
            $dateLabel = $joursFrLong[$jourIdx] . ' ' . date('j', $dateTs) . ' ' . strtolower($moisFr[$mois]);

            $source = $entry['source']      ?? '';
            $type   = $entry['type_entree'] ?? '';

            if ($source === 'inscription') {
                $borderColor = 'var(--orange)';
            } elseif ($type === 'anniversaire') {
                $borderColor = '#2E7D32';
            } else {
                $borderColor = '#1565C0';
            }
          ?>
            <div class="event-card" style="border-left-color:<?= $borderColor ?>;margin-bottom:12px">
              <div style="flex:1;min-width:0">
                <div style="color:var(--orange);font-weight:700;font-size:.88rem;margin-bottom:4px">
                  <?= htmlspecialchars($dateLabel) ?>
                </div>
                <div style="font-weight:700;font-size:.97rem;margin-bottom:4px">
                  <?= htmlspecialchars($entry['titre']) ?>
                </div>
                <?php if (!empty($entry['heure'])): ?>
                  <div class="text-sm text-muted">🕑 <?= substr($entry['heure'], 0, 5) ?></div>
                <?php endif; ?>
                <?php if (!empty($entry['note'])): ?>
                  <div class="text-sm text-muted" style="margin-top:4px"><?= htmlspecialchars($entry['note']) ?></div>
                <?php endif; ?>
              </div>
              <div style="flex-shrink:0;display:flex;align-items:center">
                <?php if ($source === 'inscription'): ?>
                  <span class="badge badge-green">Inscrit ✓</span>
                <?php elseif ($type === 'anniversaire'): ?>
                  <a href="chat.php" class="btn btn-teal btn-sm">Envoyer mes vœux 🎉</a>
                <?php else: ?>
                  <span class="text-xs text-muted">🔒 Visible uniquement par vous</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <div id="form-ajout" style="display:none;margin-top:16px">
          <div class="card">
            <h3 style="margin-bottom:20px">＋ Ajouter une date importante</h3>
            <form method="POST" action="?mois=<?= $mois ?>&annee=<?= $annee ?>">
              <input type="hidden" name="ajouter_entree" value="1">

              <div class="form-group">
                <label for="titre">Titre</label>
                <input type="text" id="titre" name="titre" required
                       placeholder="Ex : Rendez-vous médecin">
              </div>

              <div class="form-group">
                <label for="date_entree">Date</label>
                <input type="date" id="date_entree" name="date_entree" required>
              </div>

              <div class="form-group">
                <label for="heure">Heure <span class="form-hint" style="display:inline">(optionnel)</span></label>
                <input type="time" id="heure" name="heure">
              </div>

              <div class="form-group">
                <label for="type_entree">Type</label>
                <select id="type_entree" name="type_entree">
                  <option value="rdv">Rendez-vous</option>
                  <option value="anniversaire">Anniversaire</option>
                  <option value="autre">Autre</option>
                </select>
              </div>

              <div class="form-group">
                <label for="note">Note <span class="form-hint" style="display:inline">(optionnel)</span></label>
                <textarea id="note" name="note" rows="3" maxlength="200"
                          placeholder="Apporter la carte vitale…"></textarea>
              </div>

              <button type="submit" class="btn btn-primary btn-full">Ajouter à mon agenda</button>
            </form>
          </div>
        </div>

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
