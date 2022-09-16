<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task;
use Symfony\Component\HttpFoundation\Request;
use App\Form\TaskType;

class TaskController extends AbstractController
{   
    public function index(Request $request, EntityManagerInterface $doctrine): Response
    {
        $tasks = $doctrine->getRepository(Task::class)->findAll();

        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        $task->setDateCreated(new \DateTime())
            ->setIsComplete(false);
        if($form->isSubmitted() && $form->isValid()){
            $doctrine->persist($task);
            $doctrine->flush();
            return $this->redirectToRoute('index');
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'taskForm' => $form->createView(),
            'taskEditForm' => $form->createView() // recargamos otro formulario para evitar fallos 
        ]);
    }

    public function finish($id, EntityManagerInterface $doctrine): Response
    {
        $task = $doctrine->getRepository(Task::class)->findOneBy(['id' => $id]);
        $task->setIsComplete(true)
             ->setDateFinish(new \DateTime());
       
        // cargamos los datos en el modelo:
        $doctrine->persist($task);
        $doctrine->flush(); 
        return $this->redirectToRoute('index');
    }

    public function delete($id, EntityManagerInterface $doctrine): Response
    {
        $task = $doctrine->getRepository(Task::class)->findOneBy(['id' => $id]);
        $doctrine->remove($task);
        $doctrine->flush(); 
        return $this->redirectToRoute('index');
    }

    // creamos un nuevo método para editar tareas:
    public function update($id, EntityManagerInterface $doctrine, Request $request): Response
    {
        // recuperar tarea:
        $task = $doctrine->getRepository(Task::class)->findOneBy(['id' => $id]);
        dump($request->query->get('name'));
        // crear formulario y pasarle la request:
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
        // ahora no lo persistimos solo lo escribimos:
        if($form->isSubmitted() && $form->isValid()){
            $doctrine->flush();
        }

        // redireccionar:
        return $this->redirectToRoute('index');
        
    }

}
