<?php
// session.php - Gestion des sessions
// Projet Old Dating - BUT1 Informatique

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirige si l'adhérent n'est pas connecté
function requireLogin(): void
{
    if (empty($_SESSION['adherent'])) {
        header('Location: ../index.php');
        exit;
    }
}

// Redirige si l'admin n'est pas connecté
function requireAdmin(): void
{
    if (empty($_SESSION['admin'])) {
        header('Location: ../index.php');
        exit;
    }
}

function getAdherentSession(): array
{
    return $_SESSION['adherent'] ?? [];
}

function getAdminSession(): array
{
    return $_SESSION['admin'] ?? [];
}

function logout(): void
{
    session_destroy();
    header('Location: ../index.php');
    exit;
}

// Génère les initiales pour l'avatar par défaut
function getInitiales(string $prenom, string $nom = ''): string
{
    $initiales = strtoupper(substr($prenom, 0, 1));
    if ($nom) {
        $initiales .= strtoupper(substr($nom, 0, 1));
    }
    return $initiales;
}
