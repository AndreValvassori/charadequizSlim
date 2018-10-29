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
            Select IFNULL((Select count(0) from quiz where user_id = A.usuario_id), 0) as Total_Respondidos ,IFNULL(sum(A.time), 0) as Tempo_Total , IFNULL(FLOOR(avg(A.time)), 0) as Tempo_Medio from quiz Q
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
                IFNULL((Select count(0) from Quiz_Question where Quiz_id = quiz.id),0) as Tempo_Esperado,
                IFNULL((Select sum(time) from answer inner join alternative on alternative.id = alternative_id inner join question on question.id = alternative.question_id inner join quiz_question on quiz_question.question_id = question.id where quiz_question.Quiz_id = quiz.id),0) as Tempo_Total,
                IFNULL((Select floor(avg(time)) from answer inner join alternative on alternative.id = alternative_id inner join question on question.id = alternative.question_id inner join quiz_question on quiz_question.question_id = question.id where quiz_question.Quiz_id = quiz.id),0) as Tempo_Medio,
                IFNULL((Select count(0) from alternative inner join answer on answer.alternative_id = alternative.id where answer.quiz_id = quiz.id and alternative.correct = 1),0) as Total_Corretas,
                IFNULL((Select count(0) from alternative inner join answer on answer.alternative_id = alternative.id where answer.quiz_id = quiz.id and alternative.correct = 0),0) as Total_Erradas
                from quiz quiz
            where id = :quizid");
        $sqlResumo->bindParam("quizid", $args['quizid']);
        $sqlResumo->execute();
        $resultResumo = $sqlResumo->fetchAll();
        
        foreach ($resultResumo as $rowResumo) {
        $ArrayMaster = Array(
            'Tempo_Esperado' =>$rowResumo["Tempo_Esperado"],
            'Tempo_Total' =>$rowResumo["Tempo_Total"],
            'Tempo_Medio' =>$rowResumo["Tempo_Medio"],
            'Total_Corretas'       =>$rowResumo["Total_Corretas"],
            'Total_Erradas'       =>$rowResumo["Total_Erradas"]
            
        );
        }
        return $this->response->withJson($ArrayMaster, null, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
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
         
        
		$sth->bindParam("time_1",time_1 );     $sth->bindParam("alternative_id_1",alternative_id_1 );
        $sth->bindParam("time_2",time_2 );     $sth->bindParam("alternative_id_2",alternative_id_2 );
        $sth->bindParam("time_3",time_3 );     $sth->bindParam("alternative_id_3",alternative_id_3 );
        $sth->bindParam("time_4",time_4 );     $sth->bindParam("alternative_id_4",alternative_id_4 );
        $sth->bindParam("time_5",time_5 );     $sth->bindParam("alternative_id_5",alternative_id_5 );
        $sth->bindParam("time_6",time_6 );     $sth->bindParam("alternative_id_6",alternative_id_6 );
        $sth->bindParam("time_7",time_7 );     $sth->bindParam("alternative_id_7",alternative_id_7 );
        $sth->bindParam("time_8",time_8 );     $sth->bindParam("alternative_id_8",alternative_id_8 );
        $sth->bindParam("time_9",time_9 );     $sth->bindParam("alternative_id_9",alternative_id_9 );
        $sth->bindParam("time_10",time_10);    $sth->bindParam("alternative_id_10",alternative_id_10);
  
        $sth->execute();    
        
           $False = array('status' => 1);            
            return $this->response->withJson($False);
                   

    });
// Fim das rotinas do Webservice
