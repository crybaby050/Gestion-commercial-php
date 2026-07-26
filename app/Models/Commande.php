<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model représentant une commande passée par un client.
 * Une commande appartient à un seul client et contient une ou plusieurs lignes.
 */
class Commande extends Model
{
    protected string $table = 'commande';

    /**
     * Vérifie si une commande existe déjà avec ce numéro exact (doublon).
     */
    public function existeParNumero(string $numero): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE numero = ?");
        $stmt->execute([$numero]);
        return (bool) $stmt->fetch();
    }

    /**
     * Crée une commande vide (non validée, montant à 0) pour un client.
     */
    public function creer(string $numero, int $clientId): int
    {
        $sql = "INSERT INTO {$this->table} (numero, client_id) VALUES (:numero, :client_id) RETURNING id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':numero' => $numero, ':client_id' => $clientId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Ajoute une ligne de commande (produit + quantité) et recalcule le montant total.
     */
    public function ajouterLigne(int $commandeId, int $produitId, int $quantite): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO ligne_commande (commande_id, produit_id, quantite) VALUES (?, ?, ?)"
        );
        $stmt->execute([$commandeId, $produitId, $quantite]);

        $this->recalculerMontantTotal($commandeId);
    }

    /**
     * Recalcule le montant total de la commande à partir de ses lignes.
     */
    private function recalculerMontantTotal(int $commandeId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET montant_total = (
                 SELECT COALESCE(SUM(lc.quantite * p.prix_unitaire), 0)
                 FROM ligne_commande lc
                 JOIN produit p ON p.id = lc.produit_id
                 WHERE lc.commande_id = ?
             )
             WHERE id = ?"
        );
        $stmt->execute([$commandeId, $commandeId]);
    }

    /**
     * Valide une commande (règle : au moins une ligne doit exister).
     */
    public function valider(int $commandeId): bool
    {
        $nbLignes = $this->db->prepare("SELECT COUNT(*) FROM ligne_commande WHERE commande_id = ?");
        $nbLignes->execute([$commandeId]);
        if ((int) $nbLignes->fetchColumn() === 0) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET validee = TRUE WHERE id = ?");
        return $stmt->execute([$commandeId]);
    }

    /**
     * Récupère une commande avec ses lignes (produit + quantité + sous-total) et le client associé.
     */
    public function findAvecLignes(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*, u.nom AS client_nom, u.prenom AS client_prenom
             FROM {$this->table} c
             JOIN utilisateurs u ON u.id = c.client_id
             WHERE c.id = ?"
        );
        $stmt->execute([$id]);
        $commande = $stmt->fetch();
        if (!$commande) {
            return null;
        }

        $lignes = $this->db->prepare(
            "SELECT lc.quantite, p.libelle, p.prix_unitaire, (lc.quantite * p.prix_unitaire) AS sous_total
             FROM ligne_commande lc
             JOIN produit p ON p.id = lc.produit_id
             WHERE lc.commande_id = ?"
        );
        $lignes->execute([$id]);
        $commande['lignes'] = $lignes->fetchAll();

        return $commande;
    }

    /**
     * Liste toutes les commandes (vue admin), avec le nom du client.
     */
    public function toutesAvecClient(): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, u.nom AS client_nom, u.prenom AS client_prenom
             FROM {$this->table} c
             JOIN utilisateurs u ON u.id = c.client_id
             ORDER BY c.id DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Liste les commandes d'un client précis (vue client, "mes commandes").
     */
    public function parClient(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE client_id = ? ORDER BY id DESC"
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }
}