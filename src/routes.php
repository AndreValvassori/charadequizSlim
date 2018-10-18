<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes



    // get all usuarios
    $app->get('/usuarios', function ($request, $response, $args) {
         $sth = $this->db->prepare("SELECT * FROM usuario ORDER BY name");
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos, null, JSON_UNESCAPED_UNICODE);
    });
 
    // retrieve a random question
    $app->get('/question', function ($request, $response, $args) {
         $sth = $this->db->prepare("SELECT * FROM question WHERE inactive = 0 ORDER BY RAND() LIMIT 1;");
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos, null, JSON_UNESCAPED_UNICODE);
    });

    // retrieve quiz Questions and Answers
        $app->get('/quiz', function ($request, $response, $args) {
         $sth = $this->db->prepare("Select * from question");
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos, null, JSON_UNESCAPED_UNICODE);
    });

/// teste

    // retrieve quiz Questions and Answers
    $app->get('/quiz1', function ($request, $response, $args) 
    {
        $sth = $this->db->prepare("Select * from question");
        $sth->execute();
        $todos = $sth->fetchAll();

        echo $sth->rowCount();
        
        $resultado = Array();
        
        foreach ($todos as $row) {
            echo $row["id"] . "-" . $row["description"] ."<br/>";
        }
        
        $resultado = $todos;        
        
        return $this->response->withJson($resultado, null, JSON_UNESCAPED_UNICODE);
    });


$app->get('/quiz99', function ($request, $response, $args) 
    {
        $quiz = array(
            'idQuiz' => 0,
            'questions' => array(
                'QuestionID' => 0,
                'QuestionDescription' => 'Normalmente, quantos litros de sangue uma pessoa tem? Em média, quantos são retirados numa doação de sangue?',
                'QuestionMaxTime' => 60,
                'Alternatives' => array(
                    'AlternativeID' => 1,
                    'AlternativeAnswer' => 'Tem entre 2 a 4 litros. São retirados 450 mililitros',
                    'AlternativeCorrect' => 0                    
                )
            )
        );
                
        return $this->response->withJson($quiz, null, JSON_UNESCAPED_UNICODE);
    });

$app->get('/quiz100', function ($request, $response, $args) 
    {
        $selectQuiz = $this->db->prepare("Select * from quiz");
        $selectQuiz->execute();
        $resultQuiz = $selectQuiz->fetchAll();                
        
        $selectQuestion = $this->db->prepare("Select * from question LIMIT 2");
        $selectQuestion->execute();
        $resultQuestion = $selectQuestion->fetchAll();  
                
        
        
//        echo json_encode($resultQuiz, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
//        echo json_encode($resultQuestion, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
//        echo json_encode($resultAlternative, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        foreach ($resultQuestion as $rowQuestion) {
                $selectAlternative = $this->db->prepare("Select * from alternative where question_id = " . $rowQuestion["id"]);
                $selectAlternative->execute();
                $resultAlternative = $selectAlternative->fetchAll();   
            $arrayQuestion = array(
            'QuestionID'            => $rowQuestion["id"],
            'QuestionDescription'   => $rowQuestion["description"],
            'QuestionMaxTime'       => $rowQuestion["maxtime"],
            'QuestionInactive'      => $rowQuestion["inactive"],
            'Alternatives'          => $resultAlternative
        
        );
            
            echo json_encode($arrayQuestion, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
            
        foreach ($resultQuiz as $rowQuiz) {
            $resultado = array(
            'idQuiz' => $rowQuiz["id"],
            'questions' => $arrayQuestion
                    
        );
        }       
        
        //echo json_encode($resultado, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        /*        
        $products = array(
    'paper' =>  array(
        'copier' => "Copier & Multipurpose",
        'inkjet' => "Inkjet Printer",
        'laser'  => "Laser Printer",
        'photo'  => "Photographic Paper"),

    'pens' => array(
        'ball'   => "Ball Point",
        'hilite' => "Highlighters",
        'marker' => "Markers"),

    'misc' => array(
        'tape'   => "Sticky Tape",
        'glue'   => "Adhesives",
        'clips'  => "Paperclips") );       
*/        
//        return $this->response->withJson($quiz, null, JSON_UNESCAPED_UNICODE);
    }});


$app->get('/quiz110', function ($request, $response, $args) 
    {
        $selectQuiz = $this->db->prepare("Select * from quiz");
        $selectQuiz->execute();
        $resultQuiz = $selectQuiz->fetchAll();                
        
        $selectQuestion = $this->db->prepare("Select * from question LIMIT 2");
        $selectQuestion->execute();
        $resultQuestion = $selectQuestion->fetchAll();  
                
        
        
//        echo json_encode($resultQuiz, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
//        echo json_encode($resultQuestion, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
//        echo json_encode($resultAlternative, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        $arrayQuestionMASTER = array();
        
        foreach ($resultQuestion as $rowQuestion) {
                $selectAlternative = $this->db->prepare("Select * from alternative where question_id = " . $rowQuestion["id"]);
                $selectAlternative->execute();
                $resultAlternative = $selectAlternative->fetchAll();   
            $arrayQuestion = array(
            'QuestionID'            => $rowQuestion["id"],
            'QuestionDescription'   => $rowQuestion["description"],
            'QuestionMaxTime'       => $rowQuestion["maxtime"],
            'QuestionInactive'      => $rowQuestion["inactive"],
            'Alternatives'          => $resultAlternative
        
        );
            array_push($arrayQuestionMASTER, $arrayQuestion);
        }
//            echo json_encode($arrayQuestion, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        foreach ($resultQuiz as $rowQuiz) {
            $resultado = array(
            'idQuiz' => $rowQuiz["id"],
            'questions' => $arrayQuestionMASTER
                    
        );
        return $this->response->withJson($resultado, null, JSON_UNESCAPED_UNICODE);
        //echo json_encode($resultado, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        /*        
        $products = array(
    'paper' =>  array(
        'copier' => "Copier & Multipurpose",
        'inkjet' => "Inkjet Printer",
        'laser'  => "Laser Printer",
        'photo'  => "Photographic Paper"),

    'pens' => array(
        'ball'   => "Ball Point",
        'hilite' => "Highlighters",
        'marker' => "Markers"),

    'misc' => array(
        'tape'   => "Sticky Tape",
        'glue'   => "Adhesives",
        'clips'  => "Paperclips") );       
*/        
//        return $this->response->withJson($quiz, null, JSON_UNESCAPED_UNICODE);
    }});

/*
    $app->get('/quiz1', function ($request, $response, $args) 
    {
        $sth = $this->db->prepare("Select * from question");
        $sth->execute();
        $todos = $sth->fetchAll();

        echo $sth->rowCount();
        
        foreach ($todos as $row) {
            print $row["id"] . "-" . $row["description"] ."<br/>";
        }
    });

*/

    // Retrieve todo with id 
    $app->get('/todo/[{id}]', function ($request, $response, $args) {
         $sth = $this->db->prepare("SELECT * FROM tasks WHERE id=:id");
        $sth->bindParam("id", $args['id']);
        $sth->execute();
        $todos = $sth->fetchObject();
        return $this->response->withJson($todos, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    });
 
 
    // Search for todo with given search teram in their name
    $app->get('/todos/search/[{query}]', function ($request, $response, $args) {
         $sth = $this->db->prepare("SELECT * FROM tasks WHERE UPPER(task) LIKE :query ORDER BY task");
        $query = "%".$args['query']."%";
        $sth->bindParam("query", $query);
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos);
    });
 
    // Add a new todo
    $app->post('/todo', function ($request, $response) {
        $input = $request->getParsedBody();
        $sql = "INSERT INTO tasks (task) VALUES (:task)";
         $sth = $this->db->prepare($sql);
        $sth->bindParam("task", $input['task']); 
        $sth->execute();
        $input['id'] = $this->db->lastInsertId();
        return $this->response->withJson($input);
    });
        
 
    // DELETE a todo with given id
    $app->delete('/todo/[{id}]', function ($request, $response, $args) {
         $sth = $this->db->prepare("DELETE FROM tasks WHERE id=:id");
        $sth->bindParam("id", $args['id']);
        $sth->execute();
        $todos = $sth->fetchAll();
        return $this->response->withJson($todos);
    });
 
    // Update todo with given id
    $app->put('/todo/[{id}]', function ($request, $response, $args) {
        $input = $request->getParsedBody();
        $sql = "UPDATE tasks SET task=:task WHERE id=:id";
         $sth = $this->db->prepare($sql);
        $sth->bindParam("id", $args['id']);
        $sth->bindParam("task", $input['task']);
        $sth->execute();
        $input['id'] = $args['id'];
        return $this->response->withJson($input);
    });