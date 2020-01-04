<?php

namespace App\Controller;

use App\Entity\Car;
use App\Form\Car1Type;
use App\Repository\CarRepository;
use phpDocumentor\Reflection\File;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/user/car")
 */
class CarController extends AbstractController
{
    /**
     * @Route("/", name="user_car_index", methods={"GET"})
     */
    public function index(CarRepository $carRepository): Response
    {
        $user = $this->getUser();
        return $this->render('car/index.html.twig', [
            'cars' => $carRepository->findBy(['userid'=>$user->getId()]),
        ]);
    }

    /**
     * @Route("/new", name="user_car_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $car = new Car();
        $form = $this->createForm(Car1Type::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $car->setStatus('new');
            //************file upload
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
                $car->setImage($fileName);
            }
            //********file upload
            $user = $this->getUser();
            $car->setUserid($user->getid());

            $car->setStatus("new");

            $entityManager->persist($car);
            $entityManager->flush();

            return $this->redirectToRoute('user_car_index');
        }

        return $this->render('car/new.html.twig', [
            'car' => $car,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="user_car_show", methods={"GET"})
     */
    public function show(Car $car): Response
    {
        return $this->render('car/show.html.twig', [
            'car' => $car,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="user_car_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Car $car): Response
    {
        $form = $this->createForm(Car1Type::class, $car);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //************file upload
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
                $car->setImage($fileName);
            }
            //********file upload
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('user_car_index');
        }

        return $this->render('car/edit.html.twig', [
            'car' => $car,
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
     * @Route("/{id}", name="user_car_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Car $car): Response
    {
        if ($this->isCsrfTokenValid('delete'.$car->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($car);
            $entityManager->flush();
        }

        return $this->redirectToRoute('user_car_index');
    }
}
