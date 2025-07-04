<?php



class DocumentController {
    private $documentModel;
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->documentModel = new Document($this->db);
    }

    public function index() {
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
        $year = isset($_GET['year']) ? validateInput($_GET['year']) : '';

        if ($userId <= 0) {
            Response::json(false, "Valid user ID is required", 400);
        }

        $offset = ($page - 1) * $limit;

        try {
            $whereConditions = ["user_id = :user_id"];
            $params = [':user_id' => $userId];
            if (!empty($search)) {
                $whereConditions[] = "(doc_name LIKE :search OR doc_number LIKE :search OR doc_desc LIKE :search)";
                $params[':search'] = "%{$search}%";
            }

            if (!empty($year)) {
                $whereConditions[] = "doc_year = :year";
                $params[':year'] = $year;
            }

            $whereClause = implode(' AND ', $whereConditions);

            $totalRecords = $this->documentModel->getDocumentCount($whereClause, $params);

            $documents = $this->documentModel->getAllDocument($whereClause, $limit, $offset, $params);

            foreach ($documents as &$doc) {
                $dateObj = new DateTime($doc['doc_date']);
                $doc['doc_date_formatted'] = $dateObj->format('d-m-Y');
                $doc['created_at_formatted'] = date('d-m-Y H:i', strtotime($doc['created_at']));
            }
            
            Response::json(true, "Documents retrieved successfully", 200, array(
            "documents" => $documents,
            "pagination" => array(
                "current_page" => $page,
                "total_records" => (int)$totalRecords,
                "total_pages" => ceil($totalRecords / $limit),
                "limit" => $limit
            )
        ));
  

        } catch (PDOException $exception) {
            Response::json(false, "Database error: " . $exception->getMessage(), 400);
        }
    }


    public function create() {
        if (
            !isset($_POST['user_id']) || !isset($_POST['doc_name']) ||
            !isset($_POST['doc_date']) || !isset($_POST['doc_number']) ||
            !isset($_POST['doc_desc'])
        ) {
            Response::json(false, "All document fields are required", 400);
        }

         
        if (!isset($_FILES['image_path']) || $_FILES['image_path']['error'] !== UPLOAD_ERR_OK) {
            Response::json(false, "Image file is required",400);
        }

        $userId = (int)$_POST['user_id'];
        $docName = validateInput($_POST['doc_name']);
        $docDate = validateInput($_POST['doc_date']);
        $docNumber = validateInput($_POST['doc_number']);
        $docDesc = validateInput($_POST['doc_desc']);

        $dateObj = DateTime::createFromFormat('d-m-Y', $docDate);
        if (!$dateObj) {
            Response::json(false, "Invalid date format. Use dd-mm-yyyy", 400);
        }

        $docYear = $dateObj->format('Y');
        $mysqlDate = $dateObj->format('Y-m-d');

        // Handle file upload
        $uploadDir = "uploads/" . $docYear . "/";
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                Response::json(false, "Failed to create upload directory", 400);
            }
        }

        $imageFile = $_FILES['image_path'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($imageFile['tmp_name']); // Deteksi tipe sebenarnya

        if (!in_array($detectedMime, $allowedTypes)) {
            Response::json(false, "Hanya gambar JPEG, PNG, atau GIF yang diizinkan. Terdeteksi: $detectedMime", 400);
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($imageFile['size'] > $maxSize) {
            Response::json(false, "Image size must be less than 5MB", 400);
        }

        $fileName = generateFileName($imageFile['name']);
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($imageFile['tmp_name'], $filePath)) {
            Response::json(false, "Failed to save image file", 400);
        }

        try {
            $documentId = $this->documentModel->createDocument($userId, $docName, $mysqlDate, $docNumber, $docDesc, $filePath, $docYear);
            if ($documentId > 0) {
                Response::json(true, "Document uploaded successfully", 200, array(
                    "document_id" => $documentId,
                    "image_path" => $filePath,
                    "doc_year" => $docYear
                ));
            } else {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                Response::json(false, "Failed to save document to database", 400);
            }
        } catch (PDOException $exception) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            Response::json(false, "Database error: " . $exception->getMessage(), 400);
            
           
        }
    }

    public function show() {
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

        if ($userId <= 0) {
            Response::json(false, "Valid user ID is required", 400);
        }

        try {

            $albums = $this->documentModel->getDocumentByUserId($userId);
    
            $formatAlbums = array();
    
            foreach ($albums as $album) {
                $formattedAlbums[] = array(
                    "name" => $album['doc_year'],
                    "path" => "uploads/" . $album['doc_year'],
                    "fileCount" => (int)$album['document_count'],
                    "lastModified" => strtotime($album['last_modified']) * 1000 // Convert to milliseconds for Android
                );
            }

            Response::json(true, "Albums retrieved successfully", 200, array(
                            "albums" => $formattedAlbums
                        ));
        } catch (PDOException $exception) {
            Response::json(false, "Database error: " . $exception->getMessage(), 400);
        }


    }

    public function update() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['document_id']) || !isset($input['user_id'])) {
            Response::json(false, "Document ID and User ID are required", 400);
        }

        $documentId = (int)$input['document_id'];
        $userId = (int)$input['user_id'];

        $updateFields = array();
        $params = array(':document_id' => $documentId, ':user_id' => $userId);

        if (isset($input['doc_name'])) {
            $updateFields[] = "doc_name = :doc_name";
            $params[':doc_name'] = validateInput($input['doc_name']);
        }

        if (isset($input['doc_date'])) {
            $docDate = validateInput($input['doc_date']);
            $dateObj = DateTime::createFromFormat('d-m-Y', $docDate);
            if ($dateObj) {
                $updateFields[] = "doc_date = :doc_date";
                $updateFields[] = "doc_year = :doc_year";
                $params[':doc_date'] = $dateObj->format('Y-m-d');
                $params[':doc_year'] = $dateObj->format('Y');
            }
        }

        if (isset($input['doc_number'])) {
            $updateFields[] = "doc_number = :doc_number";
            $params[':doc_number'] = validateInput($input['doc_number']);
        }

        if (isset($input['doc_desc'])) {
            $updateFields[] = "doc_desc = :doc_desc";
            $params[':doc_desc'] = validateInput($input['doc_desc']);
        }

        if (empty($updateFields)) {
            Response::json(false, "No fields to update", 400);
        }

        try {
            if($this->documentModel->updateDocument($userId, $documentId, $updateFields) > 0) {
                Response::json(true, "Document updated successfully", 200);
            } else {
                Response::json(true, "Document not found or no changes made", 400);
            }
        } catch (PDOException $exception) {
            Response::json(false, "Database error: " . $exception->getMessage(), 400);
        }
    }

    public function delete() {

        $input = json_decode(file_get_contents('php://input'), true);
       

        if (!isset($input['document_id']) || !isset($input['user_id'])) {
            Response::json(false, "Document ID and User ID are required", 400);
        }

        

        $documentId = (int)$input['document_id'];
        $userId = (int)$input['user_id'];
        
        try {
            $document = $this->documentModel->getDocumentPath($documentId, $userId); 
            if(!$document) {
                Response::json(false, "Document not found or access denied", 400);
            }
            $data = $this->documentModel->deleteDocument($documentId, $userId);
            var_dump($data);
            

            if($data) {
                if(!empty($document['image_path']) && file_exists($document['image_path'])) {
                    unlink($document['image_path']);
                }

                Response::json(true, "Document deleted successfully", 200);
            } else {
                Response::json(false, "Failed to delete document", 400);
            }
        } catch (PDOException $exception) {
            Response::json(false, "Database error: " . $exception->getMessage(), 400);
        }
    }
}


?>