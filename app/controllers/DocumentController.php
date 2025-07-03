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
}


?>