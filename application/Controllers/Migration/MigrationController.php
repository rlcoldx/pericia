<?php

namespace Agencia\Close\Controllers\Migration;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Migrations\MigrationManager;

class MigrationController extends Controller
{
    private MigrationManager $migrationManager;

    public function __construct($router)
    {
        parent::__construct($router);
        $this->migrationManager = new MigrationManager();
    }

    public function index(array $params = [])
    {
        $this->setParams($params);
        
        $status = $this->migrationManager->getMigrationStatus();
        $executedMigrations = $this->migrationManager->getExecutedMigrations();

        // Formatar datas para exibição
        foreach ($status['migrations'] as &$migration) {
            if ($migration['executed_at']) {
                $date = new \DateTime($migration['executed_at']);
                $migration['executed_at_formatted'] = $date->format('d/m/Y H:i:s');
            } else {
                $migration['executed_at_formatted'] = null;
            }
        }

        $this->render('pages/migration/index.twig', [
            'page' => 'migration',
            'titulo' => 'Gerenciador de Migrations',
            'status' => $status,
            'executedMigrations' => $executedMigrations
        ]);
    }

    public function run(array $params = [])
    {
        $this->setParams($params);
        
        try {
            $results = $this->migrationManager->runPendingMigrations();
            $this->responseJson([
                'success' => true,
                'message' => 'Migrations executadas com sucesso',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            $this->responseJson([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function runSingle(array $params = [])
    {
        $this->setParams($params);
        
        $input = json_decode(file_get_contents('php://input'), true);
        $migrationName = $input['migration'] ?? $_POST['migration'] ?? $_GET['migration'] ?? null;
        
        if (!$migrationName) {
            $this->responseJson([
                'success' => false,
                'message' => 'Nome da migration não fornecido'
            ]);
            return;
        }

        try {
            $result = $this->migrationManager->runMigration($migrationName);
            $this->responseJson([
                'success' => $result['status'] === 'success',
                'message' => $result['message'],
                'result' => $result
            ]);
        } catch (\Exception $e) {
            $this->responseJson([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function rollback(array $params = [])
    {
        $this->setParams($params);
        
        try {
            $results = $this->migrationManager->rollbackLastBatch();
            $this->responseJson([
                'success' => true,
                'message' => 'Rollback executado com sucesso',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            $this->responseJson([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function status(array $params = [])
    {
        $this->setParams($params);
        
        try {
            $status = $this->migrationManager->getMigrationStatus();
            $executedMigrations = $this->migrationManager->getExecutedMigrations();
            
            // Formatar datas para exibição
            foreach ($status['migrations'] as &$migration) {
                if ($migration['executed_at']) {
                    $date = new \DateTime($migration['executed_at']);
                    $migration['executed_at'] = $date->format('d/m/Y H:i:s');
                }
            }
            
            $this->responseJson([
                'success' => true,
                'status' => $status,
                'executedMigrations' => $executedMigrations
            ]);
        } catch (\Exception $e) {
            $this->responseJson([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}

