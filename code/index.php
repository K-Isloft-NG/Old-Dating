<?php
// index.php - Page de connexion (adhérent ou administrateur)
// Projet Old Dating - BUT1 Informatique

require_once 'includes/session.php';
require_once 'config.php';
require_once 'fonctions_besoin1.php';

// Si déjà connecté, rediriger directement
if (!empty($_SESSION['adherent'])) {
    header('Location: pages/dashboard.php');
    exit;
}
if (!empty($_SESSION['admin'])) {
    header('Location: pages/admin.php');
    exit;
}

$erreur = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $mdp    = trim($_POST['mot_de_passe'] ?? '');

    if (empty($pseudo) || empty($mdp)) {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        // Tentative connexion adhérent
        $adherent = connecterAdherent($pseudo, $mdp);
        if ($adherent) {
            $_SESSION['adherent'] = $adherent;
            header('Location: pages/dashboard.php');
            exit;
        }

        // Tentative connexion administrateur
        $admin = connecterAdministrateur($pseudo, $mdp);
        if ($admin) {
            $_SESSION['admin'] = $admin;
            header('Location: pages/admin.php');
            exit;
        }

        $erreur = 'Identifiant ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Old Dating — Connexion</title>
  <link rel="stylesheet" href="css/style.css">
  <style>
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: var(--bg);
    }

    /* Blob décoratif */
    .blob {
      position: fixed;
      border-radius: 50%;
      filter: blur(80px);
      opacity: .18;
      pointer-events: none;
      z-index: 0;
    }
    .blob-1 { width: 420px; height: 420px; background: var(--orange); bottom: -80px; right: -80px; }
    .blob-2 { width: 300px; height: 300px; background: var(--teal);   top: -60px; left: -60px; }

    .login-wrap {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 460px;
      padding: 16px;
    }

    .login-logo {
      text-align: center;
      margin-bottom: 28px;
    }

    .login-logo .logo-icon {
      font-size: 2.8rem;
      display: block;
      margin-bottom: 8px;
    }

    .login-logo h1 {
      font-family: 'Lora', serif;
      font-size: 2rem;
      color: var(--orange);
      font-weight: 700;
    }

    .login-logo p {
      color: var(--muted);
      font-size: .9rem;
      margin-top: 4px;
    }

    .login-card {
      background: var(--card);
      border-radius: var(--radius);
      box-shadow: var(--shadow-lg);
      padding: 36px 32px 28px;
      border: 1px solid var(--border);
    }

    .login-card h2 {
      font-size: 1.45rem;
      margin-bottom: 6px;
      text-align: center;
    }

    .login-card .sub {
      text-align: center;
      color: var(--muted);
      font-size: .9rem;
      margin-bottom: 28px;
    }

    .password-wrap {
      position: relative;
    }

    .password-wrap input { padding-right: 80px; }

    .show-pass {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: var(--teal);
      cursor: pointer;
      font-size: .85rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
      font-family: 'DM Sans', sans-serif;
    }

    .sep {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0;
      color: var(--muted);
      font-size: .85rem;
    }

    .sep::before, .sep::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .contact-admin {
      text-align: center;
      font-size: .88rem;
      color: var(--muted);
    }

    .security-box {
      margin-top: 20px;
      background: var(--bg);
      border-radius: var(--radius-sm);
      padding: 12px 16px;
      font-size: .82rem;
      color: var(--muted);
      display: flex;
      gap: 10px;
      align-items: flex-start;
      border: 1px solid var(--border);
    }

    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: .78rem;
      color: var(--muted);
    }
  </style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<div class="login-wrap">

  <div class="login-logo">
    <span class="logo-icon">🤝</span>
    <h1>Old Dating</h1>
    <p>Rencontres amicales entre seniors</p>
  </div>

  <div class="login-card">
    <h2>Bonjour ! Connectez-vous</h2>
    <p class="sub">Entrez vos identifiants pour accéder à votre espace</p>

    <?php if ($erreur): ?>
      <div class="alert alert-error">
        ❌ <?= htmlspecialchars($erreur) ?>
        — <a href="mailto:admin@olddating.fr">Besoin d'aide ? Contactez l'administrateur</a>
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php">
      <div class="form-group">
        <label for="pseudo">Votre identifiant</label>
        <input
          type="text"
          id="pseudo"
          name="pseudo"
          placeholder="Ex : Marie75"
          value="<?= htmlspecialchars($_POST['pseudo'] ?? '') ?>"
          autocomplete="username"
          required
        >
      </div>

      <div class="form-group">
        <label for="mot_de_passe">Votre mot de passe</label>
        <div class="password-wrap">
          <input
            type="password"
            id="mot_de_passe"
            name="mot_de_passe"
            placeholder="••••••••"
            autocomplete="current-password"
            required
          >
          <button type="button" class="show-pass" onclick="togglePassword()">
            👁 Voir
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:8px">
        Se connecter →
      </button>
    </form>

    <div class="sep">Première visite ?</div>

    <p class="contact-admin">
      <a href="mailto:admin@olddating.fr">
        Contactez votre administrateur pour créer votre compte
      </a>
    </p>

    <div class="security-box">
      🔒 Votre sécurité est notre priorité. Ne partagez jamais votre mot de passe.
      Si vous avez oublié vos accès, demandez de l'aide à l'accueil de votre centre.
    </div>
  </div>

  <footer class="login-footer">
    © 2026 Old Dating – Université Gustave Eiffel<br>
    <span>Vos données sont protégées et ne sont pas partagées.</span>
  </footer>

</div>

<script>
function togglePassword() {
  const input = document.getElementById('mot_de_passe');
  const btn   = document.querySelector('.show-pass');
  if (input.type === 'password') {
    input.type = 'text';
    btn.textContent = '🙈 Masquer';
  } else {
    input.type = 'password';
    btn.textContent = '👁 Voir';
  }
}
</script>

</body>
</html>
