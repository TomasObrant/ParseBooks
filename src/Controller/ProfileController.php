<?php
namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name:'profile', methods: ['GET'])]
    public function profile(): Response
    {
        return $this->render('main/profile/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/profiles', name:'profile_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();
        $users = $userRepository->findBy(['id' => $userRepository->createQueryBuilder('u')
            ->where('u != :currentUser')
            ->setParameter('currentUser', $currentUser)
            ->getQuery()
            ->getResult(),
        ]);

        return $this->render('main/profile/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/profile/{id}', name:'profile_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('main/profile/show.html.twig', [
            'user' => $user,
        ]);
    }



}