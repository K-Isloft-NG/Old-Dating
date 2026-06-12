<?php
require_once '../includes/session.php';
require_once '../config.php';
require_once '../fonctions_besoin3_4.php';

requireAdmin();
$admin     = getAdminSession();
$initiales = getInitiales($admin['prenom'] ?? 'A', $admin['nom'] ?? '');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

$stats     = getStatistiquesAdmin();
$inactifs  = getAdherentsInactifs();
$adherents = getTousLesAdherents();

$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('CSRF validation failed');
    }
    if (isset($_POST['creer']) && !empty($_POST['nom']) && !empty($_POST['prenom']) && !empty($_POST['telephone'])) {
        $result = creerCompteAdherent($admin['id_admin'], trim($_POST['nom']), trim($_POST['prenom']), trim($_POST['telephone']));
        if ($result['succes']) {
            header('Location: admin.php?created=1');
            exit;
        }
        $msg = $result['message'];
        $msgType = 'error';
    }
    if (isset($_POST['supprimer']) && !empty($_POST['id_adherent'])) {
        $ok = supprimerCompteAdherent((int)$_POST['id_adherent'], $admin['id_admin']);
        if ($ok) {
            header('Location: admin.php?deleted=1');
            exit;
        }
        $msg = 'Erreur lors de la suppression.';
        $msgType = 'error';
    }
}
if (isset($_GET['created'])) {
    $msg = 'Compte créé avec succès ! Les identifiants ont été envoyés par SMS.';
    $msgType = 'success';
}
if (isset($_GET['deleted'])) {
    $msg = 'Compte supprimé avec succès.';
    $msgType = 'success';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Administration</title>
  <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<a href="#contenu-principal" class="skip-link">Aller au contenu principal</a>
<button type="button" class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Ouvrir le menu">☰</button>
<div class="mobile-overlay" id="mobile-overlay" onclick="toggleMobileMenu()"></div>
<div class="app-wrapper">

  <aside class="sidebar admin-sidebar">
    <div class="sidebar-logo">
      <div class="logo-name">🤝 Old Dating <span class="badge badge-orange" style="font-size:.65rem;vertical-align:middle">ADMIN</span></div>
      <div class="logo-sub">Espace administrateur</div>
    </div>
    <div class="sidebar-user">
      <div class="avatar avatar-40" style="background:rgba(255,255,255,.1);color:var(--orange)"><?= $initiales ?></div>
      <div>
        <div class="user-greeting">Administrateur</div>
        <div class="user-sub"><?= htmlspecialchars($admin['prenom'].' '.$admin['nom']) ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <a href="admin.php" class="nav-item active"><span class="nav-icon">📊</span> Tableau de bord</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../logout.php" class="nav-item" style="color:#ff8a80"><span class="nav-icon">🚪</span> Déconnexion</a>
    </div>
  </aside>

  <main class="main-content" id="contenu-principal">

    <div class="page-header">
      <div class="page-title">📊 Tableau de bord</div>
      <p style="color:var(--muted);margin-top:4px">Bienvenue, <?= htmlspecialchars($admin['prenom']) ?>. Voici l'état actuel de la plateforme Old Dating.</p>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-<?= $msgType ?? 'info' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="kpi-grid">
      <div class="kpi-card teal">
        <div class="kpi-number text-teal"><?= $stats['nb_actifs'] ?></div>
        <div class="kpi-label">Membres actifs</div>
        <div class="kpi-sub text-muted">Comptes en règle</div>
      </div>
      <div class="kpi-card orange" style="<?= $stats['nb_inactifs'] > 0 ? 'box-shadow:0 0 0 2px var(--orange)' : '' ?>">
        <div class="kpi-number text-orange"><?= $stats['nb_inactifs'] ?></div>
        <div class="kpi-label">Comptes inactifs +6 mois</div>
        <div class="kpi-sub text-orange text-sm"><?= $stats['nb_inactifs'] > 0 ? '⚠️ Action requise' : '✓ Aucun' ?></div>
      </div>
      <div class="kpi-card green">
        <div class="kpi-number text-green"><?= $stats['nb_evenements'] ?></div>
        <div class="kpi-label">Événements ce mois</div>
        <div class="kpi-sub text-muted"><?= $stats['nb_complets'] ?> complet(s)</div>
      </div>
      <div class="kpi-card red">
        <div class="kpi-number" style="color:var(--red)"><?= count($inactifs) ?></div>
        <div class="kpi-label">À supprimer</div>
        <div class="kpi-sub text-muted">Inactivité prolongée</div>
      </div>
    </div>

    <div class="section-title">➕ Créer un compte adhérent</div>
    <div class="card mb-24" style="margin-bottom:28px">
      <form method="POST" style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:flex-end">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <div class="form-group" style="margin-bottom:0">
          <label>Nom</label>
          <input type="text" name="nom" placeholder="Dupont" required>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label>Prénom</label>
          <input type="text" name="prenom" placeholder="Marie" required>
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label>Téléphone</label>
          <input type="tel" name="telephone" placeholder="0601020342" required>
        </div>
        <button type="submit" name="creer" class="btn btn-teal">✓ Créer</button>
      </form>
      <div class="form-hint" style="margin-top:8px">📱 Un SMS avec les identifiants sera envoyé automatiquement à l'adhérent.</div>
    </div>

    <div class="section-title">👥 Tous les adhérents</div>
    <div class="card" style="margin-bottom:28px">
      <div class="table-wrap">
        <table aria-label="Liste de tous les adhérents">
          <thead>
            <tr>
              <th>Membre</th>
              <th>Pseudo</th>
              <th>Téléphone</th>
              <th>Inscriptions</th>
              <th>Messages</th>
              <th>Statut</th>
              <th>Dernière co.</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($adherents as $adh): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div class="avatar avatar-40"><?= getInitiales($adh['prenom'], $adh['nom']) ?></div>
                  <div>
                    <div class="font-bold"><?= htmlspecialchars($adh['prenom'].' '.$adh['nom']) ?></div>
                    <div class="text-xs text-muted">Depuis <?= date('d/m/Y', strtotime($adh['date_creation'])) ?></div>
                  </div>
                </div>
              </td>
              <td><?= htmlspecialchars($adh['pseudo']) ?></td>
              <td class="text-muted text-sm"><?= htmlspecialchars(substr($adh['telephone'],0,2).'••••••'.substr($adh['telephone'],-2)) ?></td>
              <td><span class="badge badge-teal"><?= $adh['nb_inscriptions'] ?></span></td>
              <td><span class="badge badge-orange"><?= $adh['nb_messages'] ?></span></td>
              <td>
                <?php if ($adh['actif']): ?>
                  <span class="badge badge-green">✓ Actif</span>
                <?php else: ?>
                  <span class="badge badge-gray">Inactif</span>
                <?php endif; ?>
              </td>
              <td class="text-sm text-muted">
                <?= $adh['date_derniere_connexion'] ? date('d/m/Y', strtotime($adh['date_derniere_connexion'])) : '—' ?>
              </td>
              <td>
                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce compte ? Cette action est irréversible.')">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="id_adherent" value="<?= $adh['id_adherent'] ?>">
                  <button type="submit" name="supprimer" class="btn btn-danger btn-sm">🗑 Supprimer</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($adherents)): ?>
              <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:32px">Aucun adhérent pour l'instant.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($inactifs)): ?>
      <div class="section-title">⚠️ Comptes inactifs depuis +6 mois</div>
      <div class="card">
        <div class="table-wrap">
          <table aria-label="Comptes inactifs à supprimer">
            <thead>
              <tr>
                <th>Membre</th>
                <th>Pseudo</th>
                <th>Téléphone</th>
                <th>Dernière connexion</th>
                <th>Inactivité</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($inactifs as $inc):
                $urgent = $inc['mois_inactif'] >= 7;
              ?>
              <tr class="<?= $urgent ? 'urgent' : '' ?>">
                <td>
                  <div style="display:flex;align-items:center;gap:10px">
                    <div class="avatar avatar-40"><?= getInitiales($inc['prenom'], $inc['nom']) ?></div>
                    <div>
                      <div class="font-bold"><?= htmlspecialchars($inc['prenom'].' '.$inc['nom']) ?></div>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($inc['pseudo']) ?></td>
                <td><?= htmlspecialchars(substr($inc['telephone'],0,2).'••••••'.substr($inc['telephone'],-2)) ?></td>
                <td><?= $inc['date_derniere_connexion'] ? date('d/m/Y', strtotime($inc['date_derniere_connexion'])) : 'Jamais' ?></td>
                <td>
                  <span style="color:<?= $urgent ? 'var(--red)' : 'var(--orange)' ?>;font-weight:700">
                    <?= $inc['mois_inactif'] ?> mois
                  </span>
                </td>
                <td>
                  <form method="POST" onsubmit="return confirm('Confirmer la suppression du compte de <?= htmlspecialchars($inc['prenom'].' '.$inc['nom']) ?> ? Cette action est irréversible.')">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id_adherent" value="<?= $inc['id_adherent'] ?>">
                    <button type="submit" name="supprimer" class="btn btn-danger btn-sm">🗑 Supprimer</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
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
