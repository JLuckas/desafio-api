<?php 
    require_once 'config.php';

    function checkCredentials($email, $password) {
        $conn = connectToDatabase();

        $sql = "SELECT * FROM users WHERE email = ? AND password =?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $email, $password);
        $stmt->execute();

        $result = $stmt->get_result();
        
        $user = $result->fetch_assoc();
        

        $stmt->close();
        $conn->close();
        
        if ($user) {
            return $user;
        }

        return null;
    }

    function sendResponse($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    function connectToDatabase() {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($conn->connect_error) {
            die('Erro na conexão com o banco de dados: ' . $conn->connect_error);
        }
        return $conn;
    }

    function registerUser($request) {
        $conn = connectToDatabase();

        $userData = $request['user'];
        $pass = password_hash($userData['password'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $userData['name'], $userData['email'], $pass);

        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            sendResponse(['id' => $userId, 'message' => 'Usuário registrado com sucesso']);
        } else {
            sendResponse(['error' => 'Erro ao registrar usuário: ' . $stmt->error]);
        }

        $stmt->close();
        $conn->close();
    }

    function loginUser($request) {
        $conn = connectToDatabase();

        $userData = $request['user'];

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $userData['email']);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($userData['password'], $user['password'])) {
            sendResponse(['message' => 'Login bem-sucedido', 'user' => $user]);
        } else {
            sendResponse(['error' => 'Credenciais inválidas']);
        }

        $stmt->close();
        $conn->close();
    }

    function createClient($request) {
        $auth = checkCredentials("jeremyluckas@gmail.com", "34320000");
        if(!$auth) {
            sendResponse(['error' => 'Credenciais inválidas']);
            return;
        }


        $conn = connectToDatabase();

        $clientData = $request['client'];

        $sql = "INSERT INTO clientes (nome, idade, email, endereco_entrega, endereco_cobranca, user_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sisssi', $clientData['nome'], $clientData['idade'], $clientData['email'], $clientData['endereco_entrega'], $clientData['endereco_cobranca'], $auth['id']);

        if ($stmt->execute()) {
            $clientId = $stmt->insert_id;
            sendResponse(['id' => $clientId, 'message' => 'Cliente criado com sucesso']);
        } else {
            sendResponse(['error' => 'Erro ao criar cliente: ' . $stmt->error]);
        }

        $stmt->close();
        $conn->close();
    }

    function updateClient($request) {
        $auth = checkCredentials("jeremyluckas@gmail.com", "34320000");
        if (!$auth) {
            sendResponse(['error' => 'Credenciais inválidas']);
            return;
        }

        $conn = connectToDatabase();

        $clientId = $request['client']['id'];
        $clientData = $request['client'];

        $sql = "UPDATE clientes SET nome = ?, idade = ?, email = ?, endereco_entrega = ?, endereco_cobranca = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sisssi', $clientData['nome'], $clientData['idade'], $clientData['email'], $clientData['endereco_entrega'], $clientData['endereco_cobranca'], $clientId);

        if ($stmt->execute()) {
            sendResponse(['message' => 'Cliente atualizado com sucesso']);
        } else {
            sendResponse(['error' => 'Erro ao atualizar cliente: ' . $stmt->error]);
        }

        $conn->close();
        $stmt->close();
    }

    function getClient($request) {
        $auth = checkCredentials("jeremyluckas@gmail.com", "34320000");
        if (!$auth) {
            sendResponse(['error' => 'Credenciais inválidas']);
            return;
        }

        $conn = connectToDatabase();

        $clientId = $request['client']['id'];
        //var_dump($request);

        $sql = "SELECT * FROM clientes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $clientId);
        $stmt->execute();

        $result = $stmt->get_result();
        $client = $result->fetch_assoc();

        if ($client) {
            sendResponse(['client' => $client]);
        } else {
            sendResponse(['error' => 'Cliente não encontrado']);
        }

        $stmt->close();
        $conn->close();
    }

    function deleteClient($request) {
        $auth = checkCredentials("jeremyluckas@gmail.com", "34320000");
        if (!$auth) {
            sendResponse(['error' => 'Credenciais inválidas']);
            return;
        }

        $conn = connectToDatabase();

        $clientId = $request['client']['id'];     

        $sql = "DELETE FROM clientes WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $clientId);

        if ($stmt->execute()) {
            sendResponse(['message' => 'Cliente excluído com sucesso']);
        } else {
            sendResponse(['error' => 'Erro ao excluir cliente: ' . $stmt->error]);
        }

        $stmt->close();
        $conn->close();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $requestData = json_decode(file_get_contents('php://input'), true);

        $action = $requestData['action'];

        switch ($action) {
            case 'register':
                registerUser($requestData);
                break;
            case 'login':
                loginUser($requestData);
                break;
            case 'create':
                createClient($requestData);
                break;
            case 'update':
                updateClient($requestData);
                break;
            case 'get':
                getClient($requestData);
                break;
            case 'delete':
                deleteClient($requestData);
                break;
            default:
                sendResponse(['error' => 'Ação inválida']);
        }
    } else {
        sendResponse(['error' => 'Método inválido']);
    }
?>