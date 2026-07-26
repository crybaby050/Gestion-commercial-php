<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model représentant une facture générée après validation d'une commande.
 */
class Facture extends Model
{
    protected string $table = 'facture';

    public function existeParNumero(string $numero): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE numero = ?");
        $stmt->execute([$numero]);
        return (bool) $stmt->fetch();
    }

    /**
     * Vérifie si une facture existe déjà pour cette commande (une commande = une facture max).
     */
    public function existeParCommande(int $commandeId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE commande_id = ?");
        $stmt->execute([$commandeId]);
        return (bool) $stmt->fetch();
    }

    /**
     * Crée une facture pour une commande, avec le montant de la commande au moment de la facturation.
     */
    public function creer(string $numero, int $commandeId, float $montant): int
    {
        $sql = "INSERT INTO {$this->table} (numero, commande_id, montant)
                VALUES (:numero, :commande_id, :montant) RETURNING id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero'      => $numero,
            ':commande_id' => $commandeId,
            ':montant'     => $montant,
        ]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Récupère une facture avec sa commande, son montant versé calculé, et ses paiements.
     */
    public function findAvecPaiements(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, c.numero AS commande_numero, c.client_id
             FROM {$this->table} f
             JOIN commande c ON c.id = f.commande_id
             WHERE f.id = ?"
        );
        $stmt->execute([$id]);
        $facture = $stmt->fetch();
        if (!$facture) {
            return null;
        }

        $paiements = $this->db->prepare("SELECT * FROM paiement WHERE facture_id = ? ORDER BY date_paiement");
        $paiements->execute([$id]);
        $facture['paiements'] = $paiements->fetchAll();

        $facture['montant_verse'] = array_sum(array_column($facture['paiements'], 'montant_verse'));
        $facture['montant_restant'] = $facture['montant'] - $facture['montant_verse'];
        $facture['soldee'] = $facture['montant_restant'] <= 0;

        return $facture;
    }

    /**
     * Liste les factures non soldées ou partiellement payées (vue admin).
     */
    public function impayeesOuPartielles(): array
    {
        $stmt = $this->db->query(
            "SELECT f.*, COALESCE(SUM(p.montant_verse), 0) AS montant_verse
             FROM {$this->table} f
             LEFT JOIN paiement p ON p.facture_id = f.id
             GROUP BY f.id
             HAVING f.montant > COALESCE(SUM(p.montant_verse), 0)
             ORDER BY f.id DESC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Liste les factures liées aux commandes d'un client précis (vue client).
     */
    public function parClient(int $clientId): array
    {
        $stmt = $this->db->prepare(
            "SELECT f.*, c.numero AS commande_numero
             FROM {$this->table} f
             JOIN commande c ON c.id = f.commande_id
             WHERE c.client_id = ?
             ORDER BY f.id DESC"
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }
}