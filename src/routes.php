<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes


// Rotinas do WebService
    $app->post('/login/', function ($request, $response) {
        $input = $request->getParsedBody();
        $sth = $this->db->prepare("SELECT id,name,cellphone_number,email,'' password FROM usuario WHERE Email=:Email and Password=:Senha");
        $sth->bindParam("Email", $input['Email']);
        $sth->bindParam("Senha", $input['Senha']);
        $sth->execute();
        $usuario = $sth->fetchObject();
        return $this->response->withJson($usuario, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

    });
 
    $app->post('/registro/', function ($request, $response) {
        $input = $request->getParsedBody();
        
        $Name = $input['Name'];
        $Cellphone_Number = $input['Cellphone_Number'];
        $Email = $input['Email'];
        $Password = $input['Password'];
        
        $sth = $this->db->prepare("SELECT id FROM usuario WHERE Email=:Email");
        $sth->bindParam("Email", $Email);
        $sth->execute();
        
        $reccount = $sth->rowCount();
        
        if ($reccount == 0) 
        {
            $sqlInsert = $this->db->prepare("INSERT INTO usuario 
            (name, cellphone_number, email, password) VALUES 
            (:Name, :Cellphone_Number, :Email, :Password)");
            $sqlInsert->bindParam("Name", $Name);
            $sqlInsert->bindParam("Cellphone_Number", $Cellphone_Number);
            $sqlInsert->bindParam("Email", $Email);            
            $sqlInsert->bindParam("Password", $Password);
            $sqlInsert->execute();            
            
            
            $sqlUserID = $this->db->prepare("SELECT id FROM usuario WHERE Email=:Email");
            $sqlUserID->bindParam("Email", $input['Email']);
            $sqlUserID->execute();
            $usuario = $sqlUserID->fetchObject();
            
            return $this->response->withJson($usuario, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);  
        }
       else{
           $False = array('id' => -1);            
            return $this->response->withJson($False);
       }            

    });
 

    $app->get('/getnewquiz/[{userid}]', function ($request, $response, $args) {
        $sqlInsereQuiz = $this->db->prepare("Insert Into quiz (inactive,user_id) VALUES (0,:userid)");
        $sqlInsereQuiz->bindParam("userid", $args['userid']);
        $sqlInsereQuiz->execute();
        
        $sqlGetQuizID = $this->db->prepare("
            Insert Into quiz_question
            Select (select id from quiz where user_id = :userid ORDER BY id desc LIMIT 1),id from question ORDER BY RAND() LIMIT 10");
        $sqlGetQuizID->bindParam("userid", $args['userid']);
        $sqlGetQuizID->execute();
  
        // Monta Array questoes
        $selectQuiz = $this->db->prepare("select * from quiz where user_id = :userid ORDER BY id desc");
        $selectQuiz->bindParam("userid", $args['userid']);
        $selectQuiz->execute();
        $resultQuiz = $selectQuiz->fetchAll();                
        
        $selectQuestion = $this->db->prepare("
            Select Q.* from Question Q
                Inner Join Quiz_Question QQ on QQ.Question_ID = Q.ID
                Inner Join Quiz on Quiz.Id = QQ.Quiz_ID
            where Quiz.User_Id = :userid
        ");
        $selectQuestion->bindParam("userid", $args['userid']);
        $selectQuestion->execute();
        $resultQuestion = $selectQuestion->fetchAll();  
                
        $arrayQuestionMASTER = array();
        
        foreach ($resultQuestion as $rowQuestion) {
                $selectAlternative = $this->db->prepare("Select * from alternative where question_id = " . $rowQuestion["id"] . " ORDER BY RAND()");
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
        
        foreach ($resultQuiz as $rowQuiz) {
            $resultado = array(
            'idQuiz' => $rowQuiz["id"],
            'questions' => $arrayQuestionMASTER
                    
        );
        // Fim array Questões
        return $this->response->withJson($resultado, null, JSON_UNESCAPED_UNICODE);
        
        
//        $todos = $sth->fetchObject();
//        return $this->response->withJson($todos, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    }});

    
    $app->get('/resumoMain/[{userid}]', function ($request, $response, $args) {
        
//        {"Total_Respondidos":4,"Tempo_Total":57,"Tempo_Medio":28,
//            "quizzes": [ id,tempototal,dtcad ]
//        }
        
         $sqlResumo = $this->db->prepare("
            Select (Select count(0) from quiz where user_id = A.usuario_id) as Total_Respondidos ,sum(A.time) as Tempo_Total , FLOOR(avg(A.time)) as Tempo_Medio from quiz Q
	           Inner Join Answer A on A.quiz_id = Q.id
            where A.usuario_id = :userid");
        $sqlResumo->bindParam("userid", $args['userid']);
        $sqlResumo->execute();
        $resultResumo = $sqlResumo->fetchAll();
        
        $sqlQuiz = $this->db->prepare("
                    select 
                        Q.id as Quiz_ID,
                        (Select Sum(Time) from Answer where usuario_id = U.id and quiz_id = Q.ID group by usuario_id) as Total_Tempo,
                        Q.DtCad
                        from quiz Q
                        inner join usuario U on U.id = Q.user_id
                    where U.id = :userid");
        $sqlQuiz->bindParam("userid", $args['userid']);
        $sqlQuiz->execute();
        $resultQuiz = $sqlQuiz->fetchAll();
        
        foreach ($resultResumo as $rowResumo) {
        $ArrayMaster = Array(
            'Total_Respondidos' =>$rowResumo["Total_Respondidos"],
            'Tempo_Total'       =>$rowResumo["Tempo_Total"],
            'Tempo_Medio'       =>$rowResumo["Tempo_Medio"],
            'Quizzes'           =>$resultQuiz
            
        );
        }
        return $this->response->withJson($ArrayMaster, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    });

    $app->get('/resumoFinal/[{quizid}]', function ($request, $response, $args) {
        
//        {"Tempo Esperado:":4,"Tempo_Total":57,"Tempo_Medio":28,"Acertos":5,"Erros":5}
        
         $sqlResumo = $this->db->prepare("
            Select 
                (Select count(0) from Quiz_Question where Quiz_id = quiz.id) as Tempo_Esperado,
                (Select sum(time) from answer inner join alternative on alternative.id = alternative_id inner join question on question.id = alternative.question_id inner join quiz_question on quiz_question.question_id = question.id where quiz_question.Quiz_id = quiz.id) as Tempo_Total,
                (Select floor(avg(time)) from answer inner join alternative on alternative.id = alternative_id inner join question on question.id = alternative.question_id inner join quiz_question on quiz_question.question_id = question.id where quiz_question.Quiz_id = quiz.id) as Tempo_Medio,
                (Select count(0) from alternative inner join answer on answer.alternative_id = alternative.id where answer.quiz_id = quiz.id and alternative.correct = 1) as Total_Corretas,
                (Select count(0) from alternative inner join answer on answer.alternative_id = alternative.id where answer.quiz_id = quiz.id and alternative.correct = 0) as Total_Erradas
                from quiz quiz
            where id = :quizid");
        $sqlResumo->bindParam("quizid", $args['quizid']);
        $sqlResumo->execute();
        $resultResumo = $sqlResumo->fetchAll();

        return $this->response->withJson($resultResumo, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
    });
 

     $app->post('/enviarRespostas/', function ($request, $response) {
        $input = $request->getParsedBody();
         
/*         {"quiz_id":3,"usuario_id":1,
          "Alternatives":
                        {"Alternative_id":3,"time":20},
                        {"Alternative_id":3,"time":20},
                        {"Alternative_id":3,"time":20},
                        {"Alternative_id":3,"time":20},
                        {"Alternative_id":3,"time":20}
         }
*/        
        $quiz_id = $input["quiz_id"];
        $usuario_id = $input["usuario_id"];
         
        $alternative_id_1 =$input["Alternative_id_1"];
        $alternative_id_2 =$input["Alternative_id_2"];
        $alternative_id_3 =$input["Alternative_id_3"];
        $alternative_id_4 =$input["Alternative_id_4"];
        $alternative_id_5 =$input["Alternative_id_5"];
        $alternative_id_6 =$input["Alternative_id_6"];
        $alternative_id_7 =$input["Alternative_id_7"];
        $alternative_id_8 =$input["Alternative_id_8"];
        $alternative_id_9 =$input["Alternative_id_9"];
        $alternative_id_10=$input["Alternative_id_10"];
         
        $time_1 =$input["time_1"];
        $time_2 =$input["time_2"];
        $time_3 =$input["time_3"];
        $time_4 =$input["time_4"];
        $time_5 =$input["time_5"];
        $time_6 =$input["time_6"];
        $time_7 =$input["time_7"];
        $time_8 =$input["time_8"];
        $time_9 =$input["time_9"];
        $time_10=$input["time_10"];         

        $sth = $this->db->prepare("
            INSERT INTO `charadequiz`.`answer`
            (
                `time`,
                `inactive`,
                `usuario_id`,
                `alternative_id`,
                `quiz_id`
            )
            VALUES
            (:time_1,0,:usuario_id,:alternative_id_1,:quiz_id),
            (:time_2,0,:usuario_id,:alternative_id_2,:quiz_id),
            (:time_3,0,:usuario_id,:alternative_id_3,:quiz_id),
            (:time_4,0,:usuario_id,:alternative_id_4,:quiz_id),
            (:time_5,0,:usuario_id,:alternative_id_5,:quiz_id),
            (:time_6,0,:usuario_id,:alternative_id_6,:quiz_id),
            (:time_7,0,:usuario_id,:alternative_id_7,:quiz_id),
            (:time_8,0,:usuario_id,:alternative_id_8,:quiz_id),
            (:time_9,0,:usuario_id,:alternative_id_9,:quiz_id),
            (:time_10,0,:usuario_id,:alternative_id_10,:quiz_id);
        ");
        $sth->bindParam("quiz_id", $quiz_id);
        $sth->bindParam("usuario_id", $usuario_id);
         
        
        $sth->bindParam("time_1", $usuario_id);     $sth->bindParam("alternative_id_1", $usuario_id);
        $sth->bindParam("time_2", $usuario_id);     $sth->bindParam("alternative_id_2", $usuario_id);
        $sth->bindParam("time_3", $usuario_id);     $sth->bindParam("alternative_id_3", $usuario_id);
        $sth->bindParam("time_4", $usuario_id);     $sth->bindParam("alternative_id_4", $usuario_id);
        $sth->bindParam("time_5", $usuario_id);     $sth->bindParam("alternative_id_5", $usuario_id);
        $sth->bindParam("time_6", $usuario_id);     $sth->bindParam("alternative_id_6", $usuario_id);
        $sth->bindParam("time_7", $usuario_id);     $sth->bindParam("alternative_id_7", $usuario_id);
        $sth->bindParam("time_8", $usuario_id);     $sth->bindParam("alternative_id_8", $usuario_id);
        $sth->bindParam("time_9", $usuario_id);     $sth->bindParam("alternative_id_9", $usuario_id);
        $sth->bindParam("time_10",$usuario_id);     $sth->bindParam("alternative_id_10",$usuario_id);
         
        $sth->execute();    
        
           $False = array('status' => 1);            
            return $this->response->withJson($False);
                   

    });
// Fim das rotinas do Webservice


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
        
        foreach ($resultQuiz as $rowQuiz) {
            $resultado = array(
            'idQuiz' => $rowQuiz["id"],
            'questions' => $arrayQuestionMASTER
                    
        );
        return $this->response->withJson($resultado, null, JSON_UNESCAPED_UNICODE);

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
    /*$app->post('/todo', function ($request, $response) {
        $input = $request->getParsedBody();
        $sql = "INSERT INTO tasks (task) VALUES (:task)";
         $sth = $this->db->prepare($sql);
        $sth->bindParam("task", $input['task']); 
        $sth->execute();
        $input['id'] = $this->db->lastInsertId();
        return $this->response->withJson($input);
    });*/
        
 
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