<?php

namespace App\Controller;

use App\Entity\Admin\Comment;
use App\Entity\Admin\Reservation;
use App\Entity\User;
use App\Form\Admin\CommentType;
use App\Form\Admin\ReservationType;
use App\Form\UserType;
use App\Repository\Admin\CommentRepository;
use App\Repository\Admin\ReservationRepository;
use App\Repository\CarRepository;
use App\Repository\UserRepository;
use Cassandra\Date;
use phpDocumentor\Reflection\File;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_index", methods={"GET"})
     */
    public function index(): Response
    {
        return $this->render('user/show.html.twig');
    }

    /**
     * @Route("/comments", name="user_comments", methods={"GET"})
    */
    public function comments(CommentRepository $commentRepository): Response
    {
        $user = $this->getUser();
        $comments=$commentRepository->getAllCommentUser($user->getId());
        //dump($comments);
        //die();
       return $this->render('user/comments.html.twig',[
           'comments' => $comments,
       ]);
    }

    /**
     * @Route("/cars", name="user_cars", methods={"GET"})
     */
    public function cars(): Response
    {

        return $this->render('user/cars.html.twig');
    }

    /**
     * @Route("/reservations", name="user_reservations", methods={"GET"})
     */
    public function reservations(ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        //$reservations=$reservationRepository->findBy(['userid'=>$user->getId()]);
        $reservations = $reservationRepository->getUserReservation($user->getId());

        return $this->render('user/reservations.html.twig',[
            'reservations' => $reservations,
        ]);
    }

    /**
     * @Route("/reservation/{id}", name="user_reservation_show", methods={"GET"})
     */
    public function reservationshow($id,ReservationRepository $reservationRepository): Response
    {
        $user = $this->getUser();
        //$reservations=$reservationRepository->findBy(['userid'=>$user->getId()]);
        $reservation = $reservationRepository->getReservation($id);

        return $this->render('user/reservation_show.html.twig',[
            'reservation' => $reservation,
        ]);
    }

    /**
     * @Route("/new", name="user_new", methods={"GET","POST"})
     */
    public function new(Request $request,  UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            //************file upload***************************
            /** @var file $file */
            $file = $form['image']->getData();
            if($file){
                $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();
                // this is needed to safely include the file name as part of the URL
                try{
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                }catch(FileException $e){

                }
                $user->setImage($fileName);
            }
            //********file upload*****************************************

            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_show", methods={"GET"})
     */
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, $id, User $user,  UserPasswordEncoderInterface $passwordEncoder): Response
    {
        /*$user = $this->getUser();
        if($user->getId()!= $id) {
            echo "wrong user";
            die();
        } *///calismıyor. @paramConverter


        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //************file upload**********************************
            /** @var file $file */
            $file = $form['image']->getData();
            if($file){
                $fileName = $this->generateUniqueFileName() . '.' . $file->guessExtension();
                // this is needed to safely include the file name as part of the URL
                try{
                    $file->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                }catch(FileException $e){

                }
                $user->setImage($fileName);
            }
            //********file upload********************************************

            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }
    /**
     * @return string
     */
    private function generateUniqueFileName(){

        return md5(uniqid());
    }

    /**
     * @Route("/{id}", name="user_delete", methods={"DELETE"})
     */
    public function delete(Request $request, User $user): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_index');
    }

    /**
     * @Route("/newcomment/{id}", name="user_new_comment", methods={"GET","POST"})
     */
    public function newcomment(Request $request,$id): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
        $submittedToken = $request->request->get('token');

        if ($form->isSubmitted()) {
            if($this->isCsrfTokenValid('comment',$submittedToken)){
                $entityManager = $this->getDoctrine()->getManager();

                $comment->setStatus('new');
                $comment->setIp($_SERVER['REMOTE_ADDR']);
                $comment->setCarid($id);
                $user = $this->getUser();
                $comment->setUserid($user->getId());
                $this->addFlash('success','Yorumunuz basariyla kaydedilmiştir.');


                $entityManager->persist($comment);
                $entityManager->flush();

                return $this->redirectToRoute('car_show',['id'=>$id]);
            }

        }

        return $this->redirectToRoute('car_show',['id'=>$id]);
        /*return $this->render('car_show', [
            'comments' => $comment,
        ]);*/
    }

    /**
     * @Route("/reservation/{cid}", name="user_reservation_new", methods={"GET","POST"})
     */
    public function newreservation(Request $request,$cid,CarRepository $carRepository): Response
    {
        $car = $carRepository->findOneBy(['id'=>$cid]);

        $days = $_REQUEST["days"];
        $checkin = $_REQUEST["checkin"];
        $checkout = Date("Y-m-d H:i:s", strtotime($checkin."$days Day"));
        $checkin = Date("Y-m-d H:i:s",strtotime($checkin." 0 Days"));

        $data["total"] = $days*$car->getPrice();
        $data["days"] = $days;
        $data["checkin"] = $checkin;
        $data["checkout"] = $checkout;


        $reservation = new Reservation();
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');

        if ($form->isSubmitted()) {
            if($this->isCsrfTokenValid('form-reservation',$submittedToken)) {
                $entityManager = $this->getDoctrine()->getManager();

                $checkin = date_create_from_format("Y-m-d H:i:s",$checkin);
                $checkout = date_create_from_format("Y-m-d H:i:s",$checkout);

                $reservation->setCheckin($checkin);
                $reservation->setCheckout($checkout);
                $reservation->setStatus('new');
                $reservation->setIp($_SERVER['REMOTE_ADDR']);
                $reservation->setCarid($cid);
                $user=$this->getUser();
                $reservation->setUserid($user->getId());
                $reservation->setDays($days);
                $reservation->setTotal($data["total"]);
                $reservation->setCreatedAt(new \DateTime);//get now date

                $entityManager->persist($reservation);
                $entityManager->flush();

                return $this->redirectToRoute('user_reservations');
            }
        }

        return $this->render('user/newreservation.html.twig', [
            'reservation' => $reservation,
            'car' => $car,
            'data' => $data,
            'form' => $form->createView(),
        ]);
    }
}
