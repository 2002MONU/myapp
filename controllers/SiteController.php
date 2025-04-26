<?php

namespace app\controllers;

use app\models\Category;
use app\models\Todo;
use Yii;

use yii\web\Controller;
use yii\web\Response;


class SiteController extends Controller
{

    

    // public $layout = false;/
    /**
     * {@inheritdoc}
     */
    public function actionIndex()
    {
        $categories = Category::find()->all();
        $todos = Todo::find()->with('category')->all();
        return $this->render('index', compact('categories', 'todos'));
    }

    
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
    
        $todo = new Todo();
        $todo->name = Yii::$app->request->post('name');
        $todo->category_id = Yii::$app->request->post('category_id');
        $todo->save();
    
        return ['success' => true];
    }
    

    public function actionDelete($id)
    {
        if (Yii::$app->request->isAjax) {
            $todo = Todo::findOne($id);
            if ($todo) {
                $todo->delete();
                return $this->asJson(['success' => true]);
            }
        }
    }

    public function actionList()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        $todos = Todo::find()->with('category')->all();
        
        $todoData = [];
        foreach ($todos as $todo) {
            $todoData[] = [
                'id' => $todo->id,
                'name' => $todo->name,
                'category_name' => $todo->category ? $todo->category->name : '',
                'timestamp' => date('d M Y', strtotime($todo->created_at)),
            ];
        }
    
        return ['todos' => $todoData];
    }
    
    public function actionError()
{
    $exception = Yii::$app->errorHandler->exception;
    if ($exception !== null) {
        return $this->render('error', ['exception' => $exception]);
    }
}

}
