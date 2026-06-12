<?php
// profil.php - Création/modification pseudo et avatar
// Projet Old Dating - BUT1 Informatique

require_once '../includes/session.php';
require_once '../config.php';
require_once '../fonctions_besoin2.php';

requireLogin();

$user      = getAdherentSession();
$initiales = getInitiales($user['prenom'] ?? 'U', $user['nom'] ?? '');

// Traitement formulaire
$msg     = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_profil'])) {
    $nouveauPseudo = trim($_POST['pseudo'] ?? '');
    $avatarChoisi  = $_POST['avatar'] ?? null;

    if (empty($nouveauPseudo)) {
        $msg     = 'Le pseudo ne peut pas être vide.';
        $msgType = 'error';
    } elseif (strlen($nouveauPseudo) > 20) {
        $msg     = 'Le pseudo ne doit pas dépasser 20 caractères.';
        $msgType = 'error';
    } else {
        $result  = mettreAJourProfil($user['id_adherent'], $nouveauPseudo, $avatarChoisi);
        $msg     = $result['message'];
        $msgType = $result['succes'] ? 'success' : 'error';
        if ($result['succes']) {
            $_SESSION['adherent']['pseudo'] = $nouveauPseudo;
            $_SESSION['adherent']['avatar'] = $avatarChoisi;
            $user = getAdherentSession();
        }
    }
}

// Avatars prédéfinis
$avatars = [
    ['id' => 'avatar_1', 'label' => 'Femme cheveux argentés', 'emoji' => '👩‍🦳'],
    ['id' => 'avatar_2', 'label' => 'Homme cheveux argentés', 'emoji' => '👨‍🦳'],
    ['id' => 'avatar_3', 'label' => 'Femme cheveux bouclés',  'emoji' => '👩‍🦱'],
    ['id' => 'avatar_4', 'label' => 'Homme cheveux bouclés',  'emoji' => '👨‍🦱'],
    ['id' => 'avatar_5', 'label' => 'Femme avec lunettes',    'emoji' => '👓'],
    ['id' => 'avatar_6', 'label' => 'Homme avec lunettes',    'emoji' => '🧓'],
    ['id' => 'avatar_7', 'label' => 'Femme nature',           'emoji' => '🌻'],
    ['id' => 'avatar_8', 'label' => 'Homme barbe',            'emoji' => '🧔'],
];

$avatarActuel = $user['avatar'] ?? 'avatar_1';

$emojiActuel = '😊';
foreach ($avatars as $av) {
    if ($av['id'] === $avatarActuel) { $emojiActuel = $av['emoji']; break; }
}

// Téléphone masqué
$tel    = $user['telephone'] ?? '0600000000';
$telMsk = substr($tel, 0, 2) . '••••••' . substr($tel, -2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Mon profil</title>
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
      <a href="chat.php"       class="nav-item"><span class="nav-icon">💬</span> Messages</a>
      <a href="profil.php"     class="nav-item active"><span class="nav-icon">👤</span> Mon profil</a>
    </nav>
    <div class="sidebar-bottom">
      <a href="../logout.php" class="nav-item" style="color:var(--red)">
        <span class="nav-icon">🚪</span> Déconnexion
      </a>
    </div>
  </aside>

  <main class="main-content" id="contenu-principal">

    <div class="page-header mb-24">
      <div class="breadcrumb">
        <a href="dashboard.php">Accueil</a> › Mon profil › Pseudo &amp; Avatar
      </div>
      <div class="page-title">😊 Mon profil – Pseudo &amp; Avatar</div>
    </div>

    <div class="banner-orange mb-24">
      <div style="display:flex;align-items:center;gap:12px">
        <span style="font-size:1.8rem">🎉</span>
        <div>
          <h2 style="font-size:1.15rem;margin-bottom:2px;color:#fff">
            Bienvenue sur Old Dating, <?= htmlspecialchars($user['prenom']) ?> !
          </h2>
          <p style="font-size:.9rem;opacity:.9;color:#fff">
            Choisissez comment vous présenter aux autres membres.
          </p>
        </div>
      </div>
    </div>

    <?php if ($msg): ?>
      <div class="alert alert-<?= htmlspecialchars($msgType) ?> mb-24">
        <?= $msgType === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <form method="POST">

      <div class="grid-2" style="gap:24px;margin-bottom:24px">

        <div class="card">
          <h3 style="margin-bottom:6px">Votre pseudo</h3>
          <p class="text-sm text-muted mb-16">
            C'est le nom que verront les autres membres. Pas votre vrai nom !
          </p>

          <div class="form-group">
            <label for="pseudo-input">Choisissez votre pseudo</label>
            <input type="text"
                   id="pseudo-input"
                   name="pseudo"
                   value="<?= htmlspecialchars($user['pseudo'] ?? '') ?>"
                   maxlength="20"
                   required>
            <div class="form-hint">
              <span id="char-count"><?= strlen($user['pseudo'] ?? '') ?></span>/20 caractères
            </div>
            <div class="text-sm" style="color:var(--green);margin-top:6px">
              ✅ Ce pseudo est disponible !
            </div>
          </div>

          <div style="margin-top:14px">
            <div class="text-sm text-muted mb-8">Suggestions :</div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <button type="button" class="btn btn-outline btn-sm"
                      onclick="document.getElementById('pseudo-input').value=this.textContent.trim(); document.getElementById('char-count').textContent=this.textContent.trim().length">
                <?= htmlspecialchars($user['prenom']) ?>Soleil
              </button>
              <button type="button" class="btn btn-outline btn-sm"
                      onclick="document.getElementById('pseudo-input').value=this.textContent.trim(); document.getElementById('char-count').textContent=this.textContent.trim().length">
                <?= htmlspecialchars($user['prenom']) ?>_Lilas
              </button>
              <button type="button" class="btn btn-outline btn-sm"
                      onclick="document.getElementById('pseudo-input').value=this.textContent.trim(); document.getElementById('char-count').textContent=this.textContent.trim().length">
                <?= htmlspecialchars($user['prenom']) ?>75
              </button>
            </div>
          </div>

          <div style="margin-top:20px;background:var(--teal-light);border-radius:var(--radius-sm);padding:14px;font-size:.9rem;color:var(--teal-dark)">
            💡 Astuce : choisissez un pseudo joyeux et facile à retenir pour vos amis !
          </div>
        </div>

        <div class="card">
          <h3 style="margin-bottom:6px">Votre avatar</h3>
          <p class="text-sm text-muted mb-16">
            Votre image de profil visible dans le chat et sur vos inscriptions.
          </p>

          <div style="text-align:center;background:var(--orange-light);border-radius:var(--radius-md);padding:24px;margin-bottom:16px">
            <div class="avatar avatar-120" id="avatar-preview"
                 style="margin:0 auto;font-size:3rem">
              <?= $emojiActuel ?>
            </div>
            <div style="color:var(--orange);font-weight:600;margin-top:10px;font-family:'Lora',serif"
                 id="pseudo-preview">
              <?= htmlspecialchars($user['pseudo'] ?? '') ?>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
            <?php foreach ($avatars as $av):
              $isChecked = $avatarActuel === $av['id'];
            ?>
              <label style="cursor:pointer;text-align:center">
                <input type="radio"
                       name="avatar"
                       value="<?= $av['id'] ?>"
                       <?= $isChecked ? 'checked' : '' ?>
                       style="display:none"
                       onchange="document.getElementById('avatar-preview').textContent='<?= $av['emoji'] ?>'">
                <div style="width:72px;height:72px;border-radius:50%;display:flex;align-items:center;
                            justify-content:center;font-size:2rem;
                            border:3px solid <?= $isChecked ? 'var(--orange)' : 'var(--border)' ?>;
                            background:var(--bg);transition:border-color .2s;margin:0 auto"
                     onmouseover="this.style.borderColor='var(--orange)'"
                     onmouseout="if(!this.parentElement.querySelector('input').checked) this.style.borderColor='var(--border)'">
                  <?= $av['emoji'] ?>
                </div>
                <div style="font-size:.75rem;color:var(--text-muted);margin-top:4px">
                  <?= htmlspecialchars($av['label']) ?>
                </div>
              </label>
            <?php endforeach; ?>
          </div>

          <div style="text-align:center">
            <a href="#" class="text-sm" style="color:var(--teal)">📷 Utiliser ma propre photo</a>
          </div>
        </div>
      </div>

      <div style="background:var(--bg);border:1px solid var(--border);border-radius:var(--radius-sm);padding:16px 20px;margin-bottom:20px;font-size:.92rem">
        📱 Un SMS de confirmation sera envoyé au <strong><?= htmlspecialchars($telMsk) ?></strong>
        lorsque vous validerez votre profil. &nbsp;
        <a href="#" style="color:var(--teal)">Modifier mon numéro → Contactez l'administrateur</a>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="dashboard.php" class="btn btn-outline">Annuler</a>
        <button type="submit" name="valider_profil" class="btn btn-primary btn-lg">
          Valider mon profil ✓
        </button>
      </div>

    </form>

  </main>
</div>

<script>
const pseudoInput = document.getElementById('pseudo-input');
const charCount   = document.getElementById('char-count');
pseudoInput.addEventListener('input', function() {
    charCount.textContent = this.value.length;
    document.getElementById('pseudo-preview').textContent = this.value;
});

<?php if ($msgType === 'success'): ?>
setTimeout(function() {
    document.getElementById('pseudo-preview').textContent = document.getElementById('pseudo-input').value;
}, 100);
<?php endif; ?>
</script>
<script>
function toggleMobileMenu() {
  document.querySelector('.sidebar').classList.toggle('mobile-open');
  document.getElementById('mobile-overlay').classList.toggle('active');
}
</script>
</body>
</html>
