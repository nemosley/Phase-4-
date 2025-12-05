<?php

/*
 * Author: Ben
 * Date: 12/04/2025
 * File: game_controller.class.php
 * Description: the game controller
 *
 */


class GameController
{
    private GameModel $game_model;

    //default constructor
    public function __construct()
    {
        //create an instance of the GameModel class
        $this->game_model = GameModel::getGameModel();
    }

    /*
     * index – display all games
     */
    public function index(): void
    {
        $games = $this->game_model->get_all_games();

        if (!$games) {
            $this->error("There was a problem displaying games.");
            return;
        }

        //load index view
        $view = new GameIndex();
        $view->display($games);
    }

    /*
     * detail – display a specific game
     */
    public function detail($id): void
    {
        $game = $this->game_model->get_game_by_id((int)$id);

        if (!$game) {
            $this->error("There was a problem displaying the game id='" . $id . "'.");
            return;
        }

        //load detail view
        $view = new GameDetail();
        $view->display($game);
    }

    /*
     * create – add a new game (MVC Create Feature)
     */
    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $title = trim($_POST['title'] ?? '');
            $platform = trim($_POST['platform'] ?? '');
            $category_id = trim($_POST['category_id'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $stock = trim($_POST['stock'] ?? '');
            $available = isset($_POST['available']) ? (int)$_POST['available'] : 1;

            $errors = [];

            if ($title === '') $errors[] = "Title is required.";
            if ($platform === '') $errors[] = "Platform is required.";
            if ($category_id === '' || !ctype_digit($category_id))
                $errors[] = "Category ID must be a whole number.";
            if ($price === '' || !is_numeric($price))
                $errors[] = "Price must be a valid number.";
            if ($stock === '' || !ctype_digit($stock))
                $errors[] = "Stock must be a whole number.";
            if ($available !== 0 && $available !== 1)
                $errors[] = "Available must be 0 or 1.";

            $old = [
                'title' => $title,
                'platform' => $platform,
                'category_id' => $category_id,
                'description' => $description,
                'price' => $price,
                'stock' => $stock,
                'available' => $available,
            ];

            if (!empty($errors)) {
                //reload form with errors
                $view = new GameAdd();
                $view->display($errors, $old);
                return;
            }

            //insert into DB
            $data = [
                'title' => $title,
                'platform' => $platform,
                'category_id' => (int)$category_id,
                'description' => $description,
                'price' => (float)$price,
                'stock' => (int)$stock,
                'available' => $available,
            ];

            $new_id = $this->game_model->add_game($data);

            if ($new_id === null) {
                $errors[] = "There was a problem inserting the new game. Please try again.";
                (new GameAdd())->display($errors, $old);
                return;
            }

            //redirect to newly added game's detail page
            header("Location: " . BASE_URL . "game/detail/" . urlencode($new_id));
            exit;
        }

        //display blank form
        (new GameAdd())->display([], []);
    }

    /*
     * search – multiple keywords, AND/OR logic hidden
     */
    public function search(): void
    {
        $query_terms = trim($_GET['query-terms'] ?? '');

        if ($query_terms === '') {
            $this->index();
            return;
        }

        //default search mode
        $mode = 'AND';
        $terms = $query_terms;

        //detect OR keyword
        if (preg_match('/\bOR\b/i', $query_terms)) {
            $mode = 'OR';
            $terms = preg_replace('/\bOR\b/i', ' ', $query_terms);
            $terms = trim(preg_replace('/\s+/', ' ', $terms));
        }

        //run search
        $games = $this->game_model->search_games($terms, $mode);

        if ($games === false) {
            $this->error("An error has occurred while searching games.");
            return;
        }

        //load search results view
        $search = new GameSearch();
        $search->display($query_terms, $games);
    }

    /*
     * error – show error page
     */
    public function error($message): void
    {
        $view = new GameError();
        $view->display($message);
    }

    /*
     * magic method – handle nonexistent routes
     */
    public function __call($name, $arguments)
    {
        $this->error("Calling method '$name' caused errors. Route does not exist.");
    }
}
