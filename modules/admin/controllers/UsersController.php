<?php
namespace app\modules\admin\controllers;

use app\modules\admin\models\SearchUsers;
use app\modules\admin\models\User_roles;
use app\modules\admin\models\Users;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends Controller
{

    /**
     *
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => [
                            'admin'
                        ],
                        'denyCallback' => function ($rule, $action) {
                            return $this->redirect([
                                'site/login'
                            ]);
                        }
                    ]
                ]
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => [
                        'POST'
                    ]
                ]
            ]
        ];
    }

    /**
     * Lists all Users models.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SearchUsers();
        $request = Yii::$app->request;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        
        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider
        ]);
    }

    /**
     * Displays a single Users model.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id)
        ]);
    }

    /**
     * Creates a new Users model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Users();
        
        if ($model->load(Yii::$app->request->post())) {
            $model->setPassword($model->pass);  
            if ($model->save()) {
                $userRole = Yii::$app->authManager->getRole($model->roletitle);
                Yii::$app->authManager->assign($userRole, $model->getId());
                
                return $this->redirect([
                    'view',
                    'id' => $model->id
                ]);
            }
        }
        $roles = Yii::$app->authManager->getRoles();
        $model->pass = '';
        return $this->render('create', [
            'model' => $model,
            'roles' => $roles
        ]);
    }

    /**
     * Updates an existing Users model.
     * If update is successful, the browser will be redirected to the 'view' page.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $userOldData = Users::findOne([
            'id' => $model->id
        ]);
        if ($model->load(Yii::$app->request->post())) {
            if ($model->pass != '*****') {
                $model->setPassword($model->pass);
            } else {
                $model->pass = $userOldData->pass;
            }           
            if ($model->roletitle != reset($userOldData->getUserRoles())->name) {                
                Yii::$app->authManager->revokeAll($model->id);                
                $userRole=Yii::$app->authManager->getRole($model->roletitle);                
                Yii::$app->authManager->assign($userRole, $model->getId());
            }
            if ($model->save()) {
                return $this->redirect([
                    'view',
                    'id' => $model->id
                ]);
            }
        }
        $roles = Yii::$app->authManager->getRoles(); //User_roles::find()->all();
        $model->pass = '*****';
        return $this->render('update', [
            'model' => $model,
            'roles' => $roles
        ]);
    }

    /**
     * Deletes an existing Users model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     *
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $userToRemove = $this->findModel($id);
        if ((count($userToRemove->tasksCreated) == 0) && (count($userToRemove->tasksWork) == 0)) {
            $userToRemove->delete();
            return $this->redirect([
                'index'
            ]);
        } else {
            $errorMessage = "Невозможно удалить пользователя $userToRemove->fio ($userToRemove->username), т.к. он является создателем или исполнителем в одной или нескольких задачах!";
            Yii::$app->session->setFlash('error_message', $errorMessage);
            return $this->redirect([
                'index',
                'removeIdFailed' => $id
            ]);
        }
    }

    /**
     * Finds the Users model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     *
     * @param integer $id
     * @return Users the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Users::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
