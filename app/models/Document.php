<?php


class Document {
    private $table = "dokumen";
    private $db;

    public function __construct()
    {
        $this->db = new Database;
    }

    public function getAllDocument($filter, $limit, $offset, $params) {
        $this->db->query("SELECT id, doc_name, doc_date, doc_number, doc_desc, image_path, doc_year, created_at 
                            FROM dokumen 
                            WHERE {$filter} 
                            ORDER BY created_at DESC 
                            LIMIT :limit OFFSET :offset");
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }

        $this->db->bind(':limit', $limit);
        $this->db->bind(':offset', $offset);
        return $this->db->getAll();
    }

    public function getDocumentCount($whereClause, $params) {
        $this->db->query("SELECT COUNT(*) as total FROM dokumen WHERE {$whereClause}");
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $result = $this->db->getOne();
        return $result["total"];
    }

    public function createDocument($userId, $docName, $mysqlDate, $docNumber, $docDesc, $filePath, $docYear) {
        $this->db->query("INSERT INTO 
                            ". $this->table ." (user_id, doc_name, doc_date, doc_number, doc_desc, image_path, doc_year) 
                            VALUES (:user_id, :doc_name, :doc_date, :doc_number, :doc_desc, :image_path, :doc_year)");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':doc_name', $docName);
        $this->db->bind(':doc_date', $mysqlDate);
        $this->db->bind(':doc_number', $docNumber);
        $this->db->bind(':doc_desc', $docDesc);
        $this->db->bind(':image_path', $filePath);
        $this->db->bind(':doc_year', $docYear);

        $this->db->execute();

        return $this->db->lastInsertId();
    }

    public function updateDocument($user_id, $document_id, $updateFields) {
        $this->db->query("UPDATE ". $this->table ." 
                            SET " . implode(', ', $updateFields) . 
                            "WHERE id = :document_id 
                            AND user_id = :user_id");

        $this->db->bind(":user_id", $user_id);
        $this->db->bind(":document_id", $document_id);

        return $this->db->rowCount();
    }

}


?>