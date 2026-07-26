<?php
namespace App\Models;

use App\Core\Model;

/**
 * Model représentant un paiement effectué pour régler une facture.
 */
class Paiement extends Model
{
    protected string $table = 'paiement';

    public function existeParNumero(string $numero): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM {$this->table} WHERE numero = ?");
        $stmt->execute([$numero]);
        return (bool) $stmt->fetch();
    }

    public function creer(string $numero, int $factureId, float $montant): int
    {
        $sql = "INSERT INTO {$this->table} (numero, facture_id, montant_verse)
                VALUES (:numero, :facture_id, :montant) RETURNING id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':numero'     => $numero,
            ':facture_id' => $factureId,
            ':montant'    => $montant,
        ]);
        return (int) $stmt->fetchColumn();
    }
}