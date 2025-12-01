<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $username = $request->request->get('username');
            $plainPassword = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');
            $csrfToken = $request->request->get('_csrf_token');

            $errors = [];

            // Validate CSRF token
            if (!$this->isCsrfTokenValid('register', $csrfToken)) {
                $errors[] = 'Invalid CSRF token. Please try again.';
            }

            // Validate email
            if (empty($email)) {
                $errors[] = 'Email is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            } elseif ($userRepository->findByEmail($email)) {
                $errors[] = 'There is already an account with this email.';
            }

            // Validate username
            if (empty($username)) {
                $errors[] = 'Username is required.';
            } elseif (strlen($username) < 3 || strlen($username) > 50) {
                $errors[] = 'Username must be between 3 and 50 characters.';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
                $errors[] = 'Username can only contain letters, numbers, and underscores.';
            } elseif ($userRepository->findByUsername($username)) {
                $errors[] = 'This username is already taken.';
            }

            // Validate password
            if (empty($plainPassword)) {
                $errors[] = 'Password is required.';
            } elseif (strlen($plainPassword) < 6) {
                $errors[] = 'Password must be at least 6 characters.';
            } elseif ($plainPassword !== $confirmPassword) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('success', 'Welcome to Whiskers & Wonders! Your account has been created.');

                return $this->redirectToRoute('app_login');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }

            // Preserve entered values
            $user->setEmail($email);
            $user->setUsername($username);
        }

        return $this->render('security/register.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();

        return $this->render('security/profile.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/leaderboard', name: 'app_leaderboard')]
    public function leaderboard(UserRepository $userRepository): Response
    {
        $topUsers = $userRepository->findTopUsers(20);
        $topAdopters = $userRepository->findTopAdopters(10);

        return $this->render('security/leaderboard.html.twig', [
            'topUsers' => $topUsers,
            'topAdopters' => $topAdopters,
        ]);
    }
}
