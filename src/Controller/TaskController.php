<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use App\Form\TaskType;

class TaskController extends AbstractController
{   
    public function index(Request $request, EntityManagerInterface $doctrine): Response
    {
        $user = $this->getUser();
        // cargamos el avatar:
        $isAvatar = $user->getAvatar();
        // cargamos el nombre de usuario:
        $username = $user->getName();

        // comprobamos también si hay avatar o no:
        if($isAvatar){
            $avatar = './uploads/avatars/' . $user->getAvatar();
        }else{
            $avatar = './assets/images/avatar.png';
        }

        $tasks = $doctrine->getRepository(Task::class)->findBy(['owner' => $user]);
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $task->setDateCreated(new \DateTime())
            ->setIsComplete(false)
            ->setOwner($user); 
            $doctrine->persist($task);
            $doctrine->flush();
            return $this->redirectToRoute('index');
        }

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
            'taskForm' => $form->createView(),
            'taskEditForm' => $form->createView(),
            // cargamos para la plantilla los elementos:
            'avatar' => $avatar,
            'username' => $username
        ]);
    }

    public function finish($id, EntityManagerInterface $doctrine): Response
    {
        $user = $this->getUser();
        $task = $doctrine->getRepository(Task::class)->findOneBy(['id' => $id, 'owner' => $user]);

        if($task){
            $task->setIsComplete(true)
                 ->setDateFinish(new \DateTime());
           
            $doctrine->persist($task);
            $doctrine->flush(); 
        }
        return $this->redirectToRoute('index');
    }

    public function delete($id, EntityManagerInterface $doctrine): Response
    {
        $user = $this->getUser();
        $task = $doctrine->getRepository(Task::class)->findOneBy(['id' => $id, 'owner' => $user]);
        if($task){
            $doctrine->remove($task);
            $doctrine->flush(); 
        }

        return $this->redirectToRoute('index');
    }

    public function update($id, EntityManagerInterface $doctrine, Request $request): Response
    {
        $user = $this->getUser();
        $task = $doctrine->getRepository(Task::class)->findOneBy(['id' => $id, 'owner' => $user]);
        if($task){
            $form = $this->createForm(TaskType::class, $task);
            $form->handleRequest($request);
            if($form->isSubmitted() && $form->isValid()){
                $doctrine->flush();
            }
        }

        return $this->redirectToRoute('index');
        
    }

}
