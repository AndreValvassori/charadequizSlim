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