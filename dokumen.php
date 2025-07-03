<?php
// require_once 'config.php';

// $database = new Database();
// $db = $database->getConnection();

// $action = isset($_GET['action']) ? $_GET['action'] : '';

// switch ($action) {
//     case 'upload':
//         uploadDocument($db);
//         break;
//     case 'list':
//         getDocuments($db);
//         break;
//     case 'get_by_year':
//         getDocumentsByYear($db);
//         break;
//     case 'delete':
//         deleteDocument($db);
//         break;
//     case 'update':
//         updateDocument($db);
//         break;
//     default:
//         sendResponse(false, "Invalid action");
//         break;
// }

// function uploadDocument($db)
// {
//     // Validate required POST data
//     if (
//         !isset($_POST['user_id']) || !isset($_POST['doc_name']) ||
//         !isset($_POST['doc_date']) || !isset($_POST['doc_number']) ||
//         !isset($_POST['doc_desc'])
//     ) {
//         sendResponse(false, "All document fields are required");
//     }

//     // Validate file upload
//     if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
//         sendResponse(false, "Image file is required");
//     }

//     $userId = (int)$_POST['user_id'];
//     $docName = validateInput($_POST['doc_name']);
//     $docDate = validateInput($_POST['doc_date']);
//     $docNumber = validateInput($_POST['doc_number']);
//     $docDesc = validateInput($_POST['doc_desc']);

//     // Validate date format (expecting dd-mm-yyyy from Android)
//     $dateObj = DateTime::createFromFormat('d-m-Y', $docDate);
//     if (!$dateObj) {
//         sendResponse(false, "Invalid date format. Use dd-mm-yyyy");
//     }

//     $docYear = $dateObj->format('Y');
//     $mysqlDate = $dateObj->format('Y-m-d');

//     // Handle file upload
//     $uploadDir = "uploads/" . $docYear . "/";
//     if (!is_dir($uploadDir)) {
//         if (!mkdir($uploadDir, 0755, true)) {
//             sendResponse(false, "Failed to create upload directory");
//         }
//     }

//     $imageFile = $_FILES['image'];
//     $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
//     $finfo = new finfo(FILEINFO_MIME_TYPE);
//     $detectedMime = $finfo->file($imageFile['tmp_name']); // Deteksi tipe sebenarnya

//     if (!in_array($detectedMime, $allowedTypes)) {
//         sendResponse(false, "Hanya gambar JPEG, PNG, atau GIF yang diizinkan. Terdeteksi: $detectedMime");
//     }

//     $maxSize = 5 * 1024 * 1024; // 5MB
//     if ($imageFile['size'] > $maxSize) {
//         sendResponse(false, "Image size must be less than 5MB");
//     }

//     $fileName = generateFileName($imageFile['name']);
//     $filePath = $uploadDir . $fileName;

//     if (!move_uploaded_file($imageFile['tmp_name'], $filePath)) {
//         sendResponse(false, "Failed to save image file");
//     }

//     try {
//         $query = "INSERT INTO dokumen (user_id, doc_name, doc_date, doc_number, doc_desc, image_path, doc_year) 
//                   VALUES (:user_id, :doc_name, :doc_date, :doc_number, :doc_desc, :image_path, :doc_year)";

//         $stmt = $db->prepare($query);
//         $stmt->bindParam(':user_id', $userId);
//         $stmt->bindParam(':doc_name', $docName);
//         $stmt->bindParam(':doc_date', $mysqlDate);
//         $stmt->bindParam(':doc_number', $docNumber);
//         $stmt->bindParam(':doc_desc', $docDesc);
//         $stmt->bindParam(':image_path', $filePath);
//         $stmt->bindParam(':doc_year', $docYear);

//         if ($stmt->execute()) {
//             $documentId = $db->lastInsertId();
//             sendResponse(true, "Document uploaded successfully", array(
//                 "document_id" => $documentId,
//                 "image_path" => $filePath,
//                 "doc_year" => $docYear
//             ));
//         } else {
//             // Delete uploaded file if database insert fails
//             if (file_exists($filePath)) {
//                 unlink($filePath);
//             }
//             sendResponse(false, "Failed to save document to database");
//         }
//     } catch (PDOException $exception) {
//         // Delete uploaded file if database error occurs
//         if (file_exists($filePath)) {
//             unlink($filePath);
//         }
//         sendResponse(false, "Database error: " . $exception->getMessage());
//     }
// }

// function getDocuments($db)
// {
//     $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
//     $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
//     $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
//     $search = isset($_GET['search']) ? validateInput($_GET['search']) : '';
//     $year = isset($_GET['year']) ? validateInput($_GET['year']) : '';

//     if ($userId <= 0) {
//         sendResponse(false, "Valid user ID is required");
//     }

//     $offset = ($page - 1) * $limit;

//     try {
//         // Build query with filters
//         $whereConditions = ["user_id = :user_id"];
//         $params = [':user_id' => $userId];

//         if (!empty($search)) {
//             $whereConditions[] = "(doc_name LIKE :search OR doc_number LIKE :search OR doc_desc LIKE :search)";
//             $params[':search'] = "%{$search}%";
//         }

//         if (!empty($year)) {
//             $whereConditions[] = "doc_year = :year";
//             $params[':year'] = $year;
//         }

//         $whereClause = implode(' AND ', $whereConditions);

//         // Get total count
//         $countQuery = "SELECT COUNT(*) as total FROM dokumen WHERE {$whereClause}";
//         $countStmt = $db->prepare($countQuery);
//         foreach ($params as $key => $value) {
//             $countStmt->bindValue($key, $value);
//         }
//         $countStmt->execute();
//         $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

//         // Get documents
//         $query = "SELECT id, doc_name, doc_date, doc_number, doc_desc, image_path, doc_year, created_at 
//                   FROM dokumen 
//                   WHERE {$whereClause} 
//                   ORDER BY created_at DESC 
//                   LIMIT :limit OFFSET :offset";

//         $stmt = $db->prepare($query);
//         foreach ($params as $key => $value) {
//             $stmt->bindValue($key, $value);
//         }
//         $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
//         $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

//         $stmt->execute();
//         $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Format dates for Android
//         foreach ($documents as &$doc) {
//             $dateObj = new DateTime($doc['doc_date']);
//             $doc['doc_date_formatted'] = $dateObj->format('d-m-Y');
//             $doc['created_at_formatted'] = date('d-m-Y H:i', strtotime($doc['created_at']));
//         }

//         sendResponse(true, "Documents retrieved successfully", array(
//             "documents" => $documents,
//             "pagination" => array(
//                 "current_page" => $page,
//                 "total_records" => (int)$totalRecords,
//                 "total_pages" => ceil($totalRecords / $limit),
//                 "limit" => $limit
//             )
//         ));
//     } catch (PDOException $exception) {
//         sendResponse(false, "Database error: " . $exception->getMessage());
//     }
// }

// function getDocumentsByYear($db)
// {
//     $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

//     if ($userId <= 0) {
//         sendResponse(false, "Valid user ID is required");
//     }

//     try {
//         $query = "SELECT doc_year, COUNT(*) as document_count, 
//                          MAX(created_at) as last_modified
//                   FROM dokumen 
//                   WHERE user_id = :user_id 
//                   GROUP BY doc_year 
//                   ORDER BY doc_year DESC";

//         $stmt = $db->prepare($query);
//         $stmt->bindParam(':user_id', $userId);
//         $stmt->execute();

//         $albums = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         // Format for Android AlbumAdapter
//         $formattedAlbums = array();
//         foreach ($albums as $album) {
//             $formattedAlbums[] = array(
//                 "name" => $album['doc_year'],
//                 "path" => "uploads/" . $album['doc_year'],
//                 "fileCount" => (int)$album['document_count'],
//                 "lastModified" => strtotime($album['last_modified']) * 1000 // Convert to milliseconds for Android
//             );
//         }

//         sendResponse(true, "Albums retrieved successfully", array(
//             "albums" => $formattedAlbums
//         ));
//     } catch (PDOException $exception) {
//         sendResponse(false, "Database error: " . $exception->getMessage());
//     }
// }

// function deleteDocument($db)
// {
//     $input = json_decode(file_get_contents('php://input'), true);

//     if (!isset($input['document_id']) || !isset($input['user_id'])) {
//         sendResponse(false, "Document ID and User ID are required");
//     }

//     $documentId = (int)$input['document_id'];
//     $userId = (int)$input['user_id'];

//     try {
//         // Get document info first
//         $selectQuery = "SELECT image_path FROM dokumen WHERE id = :document_id AND user_id = :user_id";
//         $selectStmt = $db->prepare($selectQuery);
//         $selectStmt->bindParam(':document_id', $documentId);
//         $selectStmt->bindParam(':user_id', $userId);
//         $selectStmt->execute();

//         if ($selectStmt->rowCount() === 0) {
//             sendResponse(false, "Document not found or access denied");
//         }

//         $document = $selectStmt->fetch(PDO::FETCH_ASSOC);

//         // Delete from database
//         $deleteQuery = "DELETE FROM dokumen WHERE id = :document_id AND user_id = :user_id";
//         $deleteStmt = $db->prepare($deleteQuery);
//         $deleteStmt->bindParam(':document_id', $documentId);
//         $deleteStmt->bindParam(':user_id', $userId);

//         if ($deleteStmt->execute()) {
//             // Delete image file
//             if (!empty($document['image_path']) && file_exists($document['image_path'])) {
//                 unlink($document['image_path']);
//             }

//             sendResponse(true, "Document deleted successfully");
//         } else {
//             sendResponse(false, "Failed to delete document");
//         }
//     } catch (PDOException $exception) {
//         sendResponse(false, "Database error: " . $exception->getMessage());
//     }
// }

// function updateDocument($db)
// {
//     $input = json_decode(file_get_contents('php://input'), true);

//     if (!isset($input['document_id']) || !isset($input['user_id'])) {
//         sendResponse(false, "Document ID and User ID are required");
//     }

//     $documentId = (int)$input['document_id'];
//     $userId = (int)$input['user_id'];

//     $updateFields = array();
//     $params = array(':document_id' => $documentId, ':user_id' => $userId);

//     // Build dynamic update query
//     if (isset($input['doc_name'])) {
//         $updateFields[] = "doc_name = :doc_name";
//         $params[':doc_name'] = validateInput($input['doc_name']);
//     }

//     if (isset($input['doc_date'])) {
//         $docDate = validateInput($input['doc_date']);
//         $dateObj = DateTime::createFromFormat('d-m-Y', $docDate);
//         if ($dateObj) {
//             $updateFields[] = "doc_date = :doc_date";
//             $updateFields[] = "doc_year = :doc_year";
//             $params[':doc_date'] = $dateObj->format('Y-m-d');
//             $params[':doc_year'] = $dateObj->format('Y');
//         }
//     }

//     if (isset($input['doc_number'])) {
//         $updateFields[] = "doc_number = :doc_number";
//         $params[':doc_number'] = validateInput($input['doc_number']);
//     }

//     if (isset($input['doc_desc'])) {
//         $updateFields[] = "doc_desc = :doc_desc";
//         $params[':doc_desc'] = validateInput($input['doc_desc']);
//     }

//     if (empty($updateFields)) {
//         sendResponse(false, "No fields to update");
//     }

//     try {
//         $query = "UPDATE dokumen SET " . implode(', ', $updateFields) .
//             " WHERE id = :document_id AND user_id = :user_id";

//         $stmt = $db->prepare($query);
//         foreach ($params as $key => $value) {
//             $stmt->bindValue($key, $value);
//         }

//         if ($stmt->execute() && $stmt->rowCount() > 0) {
//             sendResponse(true, "Document updated successfully");
//         } else {
//             sendResponse(false, "Document not found or no changes made");
//         }
//     } catch (PDOException $exception) {
//         sendResponse(false, "Database error: " . $exception->getMessage());
//     }
// }
