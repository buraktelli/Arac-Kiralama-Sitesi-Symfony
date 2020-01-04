<?php

namespace App\Controller;

use App\Entity\Admin\Message;
use App\Entity\Car;
use App\Entity\Setting;
use App\Form\Admin\MessageType;
use App\Repository\Admin\CommentRepository;
use App\Repository\CarRepository;
use App\Repository\ImageRepository;
use App\Repository\SettingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Bridge\Google\Smtp\GmailTransport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index(SettingRepository $settingRepository,CarRepository $carRepository)
    {
        $data=$settingRepository ->findAll();
        $slider=$carRepository->findBy(['status'=>'True'],['id'=>'DESC'],5);
        $cars=$carRepository->findBy(['status'=>'True'],['title'=>'DESC'],4);
        $lastcars=$carRepository->findBy(['status'=>'True'],['id'=>'DESC'],8);


        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'data'=>$data,
            'slider'=>$slider,
            'cars'=>$cars,
            'lastcars'=>$lastcars,
        ]);
    }
    /**
     * @Route("/car/{id}", name="car_show", methods={"GET"})
     */
    public function show(Car $car,$id,ImageRepository $imageRepository,CommentRepository $commentRepository,CarRepository $carRepository): Response
    {
        $images=$imageRepository->findBy(['car'=>$id]);
        $comments=$commentRepository->findBy(['carid'=>$id, 'status'=>'True']);
        //$cars=$carRepository->findBy(['carid'=>$id, 'status'=>'True']);

        return $this->render('home/carshow.html.twig', [
            'car' => $car,
            'images' => $images,
            //'cars' => $cars,
            'comments' => $comments,
        ]);
    }

    /**
     * @Route("/about", name="home_about")
     */
    public function about(SettingRepository $settingRepository): Response
    {
        $data=$settingRepository ->findAll();
        return $this->render('home/aboutus.html.twig', [
            'data'=>$data,
        ]);
    }

    /**
     * @Route("/contact", name="home_contact", methods={"GET","POST"})
     */
    public function contact(SettingRepository $settingRepository,Request $request): Response
    {
        $data=$settingRepository ->findAll();
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);
        $submittedToken = $request->request->get('token');

        //dump($request);
        //die();

        if ($form->isSubmitted()) {
            if($this->isCsrfTokenValid('form-message',$submittedToken)){
                //echo "girdi";
                //die();
                $entityManager = $this->getDoctrine()->getManager();
                $message->setStatus('new');
                $message->setIp($_SERVER['REMOTE_ADDR']);
                $entityManager->persist($message);
                $entityManager->flush();
                $this->addFlash('success','Mesajınız basariyla kaydedilmiştir.');

                //--------------------SEND EMAIL ---------------------------------------------------
                $email = (new Email())
                    ->from($data[0]->getSmtpemail())
                    ->to($form['email']->getData())
                    //->cc('cc@example.com')
                    //->bcc('bcc@example.com')
                    //->replyTo('fabien@example.com')
                    //->priority(Email::PRIORITY_HIGH)
                    ->subject('All Cars Your Request')
                    //->text('Sending emails is fun again!')
                    ->html("Dear ".$form['name']->getData() ." <br>
                    <p>We will evaluate your request and contact you as soon as possible!</p>
                    Thank you for your message <br>
                    =======================================
                    <br>" .$data[0]->getCompany()."<br>
                    Address : ".$data[0]->getAddress()."<br>
                    Phone : ".$data[0]->getPhone()."<br>"
                    );

                $transport = new GmailTransport($data[0]->getSmtpemail(),$data[0]->getSmtppassword());
                $mailer = new Mailer($transport);
                $mailer->send($email);

                /** @var Symfony\Component\Mailer\SentMessage $sentEmail */
                //$sentEmail = $mailer->send($email);
                // $messageId = $sentEmail->getMessageId();

                // ...

                //---------------------SEND EMAİL ----------------------------------------------
                return $this->redirectToRoute('home_contact');
            }

        }

        $data=$settingRepository ->findAll();
        return $this->render('home/contact.html.twig', [
            'data'=>$data,
            'form' => $form->createView(),
        ]);
    }
}
