<?php
/**
 * Fonction pour vérifier si l'utilisateur a la permission d'accéder à l'administration
 * @param int $userId L'ID de l'utilisateur (idacount)
 * @return bool True si l'utilisateur est admin, False sinon
 */
function checkAdminPermission($userId) {
    // Convertir en entier pour éviter les problèmes de type
    $userId = intval($userId);
    
    // Vérifier si l'utilisateur a l'ID 1 (compte admin principal)
    if ($userId === 1) {
        return true;
    }
    
    // Si vous voulez autoriser plusieurs admins, vous pouvez utiliser un tableau:
    // $adminIds = [1, 2, 3]; // IDs des comptes admin
    // return in_array($userId, $adminIds);
    
    return false;
}
?>