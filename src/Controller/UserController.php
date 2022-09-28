<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
// cargamos las siguientes librerías para poder cerrar sesión antes de borrar usuario:
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use App\Entity\User;
use App\Form\UserType;
use App\Form\ResetType;

class UserController extends AbstractController
{   
    public function login(AuthenticationUtils $authenticationUtils, Request $request): Response 
    {   
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = "";

        $alert = array(
            'show' => $request->query->get("show") ? $request->query->get("show") : '',
            'status' => $request->query->get("status") ? $request->query->get("status") : '',
            'message' => $request->query->get("message") ? $request->query->get("message") : ''
        );

        if($error){
            $alert = array(
                'show' => 'show',
                'status' => 'alert-warning',
                'message' => 'Error de autentiación, usuario o contraseñas incorrectos'
            );
        }

        $user = new User();
        $registrationForm = $this->createForm(UserType::class, $user); 
        
        return $this->render('user/login.html.twig', [
            'registration_form' => $registrationForm->createView(),
            'last_username' => $lastUsername, 
            'error' => $error,
            'alert' => $alert 
        ]);
    }

    public function logout(): void {}
    
    public function register(
        Request $request, 
        EntityManagerInterface $doctrine, 
        UserPasswordHasherInterface $passwordHasher): Response
    {   
        $user = new User();
        $registrationForm = $this->createForm(UserType::class, $user); 

        $registrationForm->handleRequest($request);
        if($registrationForm->isSubmitted()){
            $email = $registrationForm->get('email')->getData();
            $emailExists = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
            if($emailExists){
                $alert = array(
                    'show' => 'show',
                    'status' => 'alert-danger',
                    'message' => 'El email ya se encuentra registrado.'
                );
                return $this->redirectToRoute('login', $alert);
            }

            if($registrationForm->isValid()){
                $user->setRoles(['ROLE_USER']);
                $plainPassword = $registrationForm->get('password')->getData();
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);

                $doctrine->persist($user);
                $doctrine->flush();
                $alert = array(
                    'show' => 'show',
                    'status' => 'alert-success',
                    'message' => 'Usuario registrado con éxito. Ya puede iniciar sesión.'
                );
                return $this->redirectToRoute('login', $alert);
            }
        }
    }

    public function profile(
        Request $request, 
        EntityManagerInterface $doctrine, 
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger 
    ): Response
    {
        $user = $this->getUser();
        $isAvatar = $user->getAvatar();
        $username = $user->getName();

        $alert = array(
            'show' => $request->query->get("show") ? $request->query->get("show") : '',
            'status' => $request->query->get("status") ? $request->query->get("status") : '',
            'message' => $request->query->get("message") ? $request->query->get("message") : ''
        );

        if($isAvatar){
            $avatar = './uploads/avatars/' . $user->getAvatar();
        }else{
            $avatar = './assets/images/avatar.png';
        }

        $oldPassword = $user->getPassword();
        $userEditForm = $this->createForm(UserType::class, $user); 
        
        $userEditForm->handleRequest($request);
        
        if($userEditForm->isSubmitted()){
            if($userEditForm->isValid()){
                $file = $userEditForm->get('avatar')->getData();
                if($file){
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
                    try{
                        $file->move(
                            $this->getParameter('avatars'), 
                            $newFilename
                        );
                    }catch(FileException $e){
                        // ignoramos el error y seguimos adelante
                    }

                    $user->setAvatar($newFilename);
                }else{ 
                    $user->setAvatar($isAvatar);
                }

                $plainPassword = $userEditForm->get('password')->getData();
                if($plainPassword){
                    $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hashedPassword);
                }else{
                    $user->setPassword($oldPassword);
                }

                $doctrine->flush();

                $alert = array(
                    'show' => 'show',
                    'status' => 'alert-success',
                    'message' => 'Usuario actualizado con éxito.'
                );

                return $this->redirectToRoute('profile', $alert);
            }
        }

        return $this->render('user/profile.html.twig', [
            'user_edit_form' => $userEditForm->createView(),
            'avatar' => $avatar, 
            'username' => $username,
            'alert' => $alert
        ]);
    }

    public function send_reset_email(
        Request $request, 
        EntityManagerInterface $doctrine, 
        MailerInterface $mailer){
        $email = $request->get('reset_email');
        $user = $doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if($user){
            $prefix = hash('sha256', $_ENV['START_KEY']);
            $sufix = substr(hash('sha256', $_ENV['END_KEY']), 0, 16);
            $hashId = openssl_encrypt($user->getId(), $_ENV['HASH_METHOD'], $prefix, 0, $sufix);

            $urlSource = "http://localhost:5000/reset_password/" . base64_encode($hashId);
            echo var_dump($urlSource);

            $emailToSend = (new Email())
                            ->from('pytonicus@gmail.com')
                            ->to($email)
                            ->subject('ASUNTO: Recuperar contraseña')
                            ->text('reestablecer contraseña')
                            ->html('<a href="' . $urlSource . '">' . $urlSource . '</a>');
            $mailer->send($emailToSend);

            
            $alert = array(
                'show' => 'show',
                'status' => 'alert-success',
                'message' => 'Se ha enviado un email con un enlace para restablecer contraseña.'
            );
            
        }else{
            $alert = array(
                'show' => 'show',
                'status' => 'alert-warning',
                'message' => 'El email no se encuentra registrado en el sistema.'
            );
        }

        return $this->redirectToRoute('login', $alert);
        /* Este código lo usamos para depurar el correo:
        return new Response(
            '<html><body>TESTING DE EMAIL</body></html>'
        ); */
    }

    public function reset_password(
        $hashId,
        Request $request,
        EntityManagerInterface $doctrine,
        UserPasswordHasherInterface $passwordHasher
        )
    {
        $prefix = hash('sha256', $_ENV['START_KEY']);
        $sufix = substr(hash('sha256', $_ENV['END_KEY']), 0, 16);
        $id = openssl_decrypt(base64_decode($hashId), $_ENV['HASH_METHOD'], $prefix, 0, $sufix);
        
        $user = $doctrine->getRepository(User::class)->findOneBy(['id' => $id]);
        if($user){
            $newPasswordForm = $this->createForm(ResetType::class); 
            $newPasswordForm->handleRequest($request);
        
            if($newPasswordForm->isSubmitted()){
                if($newPasswordForm->isValid()){               
                    $plainPassword = $newPasswordForm['password']['first'];
                    if($plainPassword){
                        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword->getData());
                        $user->setPassword($hashedPassword);
                    }else{
                        $user->setPassword($oldPassword);
                    }
                    $doctrine->flush();
                    $alert = array(
                        'show' => 'show',
                        'status' => 'alert-success',
                        'message' => 'La contraseña ha sido reestablecida con éxito, ya puede iniciar sesión.'
                    );
                    return $this->redirectToRoute('login', $alert);
                }
            }
        }else{
            $alert = array(
                'show' => 'show',
                'status' => 'alert-danger',
                'message' => 'Ha habido un error al reestablecer la contraseña, por favor inténtelo de nuevo.'
            );
            return $this->redirectToRoute('login', $alert); 
        }

        return $this->render('user/reset_password.html.twig', [
            'new_password_form' => $newPasswordForm->createView(),
        ]);
    }

    // creamos el método para eliminar el usuario y para cerrar sesión:
    public function delete_user(
        EntityManagerInterface $doctrine,
        TokenStorageInterface $tokenStorage): Response
    {
        // recuperamos el usuario que esta en sesión:
        $user = $this->getUser();
        
        if($user){
            // cerramos la sesión vaciando el token:
            $tokenStorage->setToken();

            // borramos al usuario si realmente existe:
            $doctrine->remove($user);
            $doctrine->flush(); 
        }
        // notificamos que ha sido dado de baja en login:
        $alert = array(
            'show' => 'show',
            'status' => 'alert-danger',
            'message' => 'Su usuario ha sido dado de baja. Ya no podrá iniciar sesión con el'
        );
        return $this->redirectToRoute('login', $alert); 

    }
}
