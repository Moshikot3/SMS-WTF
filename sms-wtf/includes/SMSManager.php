<?php
require_once __DIR__ . '/Database.php';

class SMSManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function addPhoneNumber($phoneNumber, $displayName, $createdBy) {
        return $this->db->insert('phone_numbers', [
            'phone_number' => $phoneNumber,
            'display_name' => $displayName,
            'created_by' => $createdBy
        ]);
    }

    public function updatePhoneNumber($id, $phoneNumber, $displayName) {
        return $this->db->update(
            'phone_numbers',
            [
                'phone_number' => $phoneNumber,
                'display_name' => $displayName
            ],
            'id = ?',
            [$id]
        );
    }

    public function deletePhoneNumber($id) {
        return $this->db->delete('phone_numbers', 'id = ?', [$id]);
    }

    public function getPhoneNumbers($activeOnly = false) {
        $sql = "SELECT * FROM phone_numbers";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY display_name, phone_number";
        
        return $this->db->fetchAll($sql);
    }

    public function getPhoneNumber($id) {
        return $this->db->fetchOne(
            "SELECT * FROM phone_numbers WHERE id = ?",
            [$id]
        );
    }

    public function togglePhoneNumberStatus($id) {
        return $this->db->query(
            "UPDATE phone_numbers SET is_active = NOT is_active WHERE id = ?",
            [$id]
        );
    }

    public function addSMS($phoneNumber, $senderNumber, $senderName, $message, $receivedAt) {
        return $this->db->insert('sms_messages', [
            'phone_number' => $phoneNumber,
            'sender_number' => $senderNumber,
            'sender_name' => $senderName,
            'message' => $message,
            'received_at' => $receivedAt
        ]);
    }

    public function getSMSMessages($phoneNumber = null, $limit = 50, $offset = 0) {
        $sql = "SELECT s.*, p.display_name as phone_display_name 
                FROM sms_messages s 
                LEFT JOIN phone_numbers p ON s.phone_number = p.phone_number";
        $params = [];

        if ($phoneNumber) {
            $sql .= " WHERE s.phone_number = ?";
            $params[] = $phoneNumber;
        }

        $sql .= " ORDER BY s.received_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function getSMSCount($phoneNumber = null) {
        $sql = "SELECT COUNT(*) as count FROM sms_messages";
        $params = [];

        if ($phoneNumber) {
            $sql .= " WHERE phone_number = ?";
            $params[] = $phoneNumber;
        }

        $result = $this->db->fetchOne($sql, $params);
        return $result['count'];
    }

    public function deleteSMS($id) {
        return $this->db->delete('sms_messages', 'id = ?', [$id]);
    }

    public function getLatestSMS($phoneNumber, $limit = 10) {
        return $this->db->fetchAll(
            "SELECT * FROM sms_messages WHERE phone_number = ? ORDER BY received_at DESC LIMIT ?",
            [$phoneNumber, $limit]
        );
    }

    public function searchSMS($searchTerm, $phoneNumber = null, $limit = 50) {
        $sql = "SELECT s.*, p.display_name as phone_display_name 
                FROM sms_messages s 
                LEFT JOIN phone_numbers p ON s.phone_number = p.phone_number
                WHERE (s.message LIKE ? OR s.sender_number LIKE ? OR s.sender_name LIKE ?)";
        
        $params = ["%$searchTerm%", "%$searchTerm%", "%$searchTerm%"];

        if ($phoneNumber) {
            $sql .= " AND s.phone_number = ?";
            $params[] = $phoneNumber;
        }

        $sql .= " ORDER BY s.received_at DESC LIMIT ?";
        $params[] = $limit;

        return $this->db->fetchAll($sql, $params);
    }

    public function getPhoneNumberByNumber($phoneNumber) {
        return $this->db->fetchOne(
            "SELECT * FROM phone_numbers WHERE phone_number = ?",
            [$phoneNumber]
        );
    }
}
?>
