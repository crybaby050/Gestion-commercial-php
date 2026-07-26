<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model représentant un produit vendu par le magasin.
 */
class Produit extends Model
{
    protected string $table = 'produit';

    public const QUANTITE_MIN = 1;
    public const PRIX_MIN = 1;

    /**
     * Vérifie si un produit existe déjà avec ce libellé exact (doublon).
     */
    public function existeParLibelle(string $libelle): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE libelle = ?");
        $stmt->execute([$libelle]);
        return (bool) $stmt->fetch();
    }

    /**
     * Recherche des produits dont le libellé contient le terme recherché.
     */
    public function rechercherParLibelle(string $terme): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE libelle ILIKE ? ORDER BY libelle");
        $stmt->execute(['%' . $terme . '%']);
        return $stmt->fetchAll();
    }

    /**
     * Crée un nouveau produit et retourne l'id généré.
     */
    public function creer(array $donnees): int
    {
        $sql = "INSERT INTO {$this->table} (libelle, quantite_stock, prix_unitaire)
                VALUES (:libelle, :quantite_stock, :prix_unitaire)
                RETURNING id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':libelle'        => $donnees['libelle'],
            ':quantite_stock' => $donnees['quantite_stock'],
            ':prix_unitaire'  => $donnees['prix_unitaire'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Met à jour la quantité en stock d'un produit (après une vente).
     */
    public function mettreAJourStock(int $id, int $nouvelleQuantite): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET quantite_stock = ? WHERE id = ?");
        return $stmt->execute([$nouvelleQuantite, $id]);
    }
}