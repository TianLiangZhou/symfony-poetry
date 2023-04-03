<?php

namespace App\Controller;

use OctopusPress\Bundle\Controller\Admin\AdminController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AdminController
{
    #[Route("/menu/question", name: 'question', options: ['name' => '问题', 'parent' => 'post', 'sort' => 6, 'link' => '/app/content/question'])]
    #[Route('/menu/dynasty', name: 'dynasty', options: ['name' => '朝代', 'parent' => 'post', 'sort' => 4, 'link' => '/app/taxonomy/dynasty'])]
    public function menu(): JsonResponse
    {
        return $this->json([]);
    }
}
