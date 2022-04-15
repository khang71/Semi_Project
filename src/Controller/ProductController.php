<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\Quantity;
use App\Entity\Category;
use App\Form\ProductType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Event\AuthenticationEvent;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductController extends AbstractController
{
    #[Route('/', name: 'product_list')]
    public function listAction(ManagerRegistry $doctrine): Response
    {
        $products = $doctrine->getRepository('App:Product')->findAll();
        // $categoryName = $products->getCategory()->getCatName()->toArray();
        $categories = $doctrine->getRepository('App:Category')->findAll();
        return $this->render('product/index.html.twig', ['products' => $products,
            'categories'=>$categories
        ]);
    }
    #[Route('/product', name: 'product')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $category = new Category();
        $category->setCatName('Computer Peripherals');

        $product = new Product();
        $product->setName('Keyboard');
        $product->setPrice(19.99);
        $product->setQuantity(2);
        $product->setDate(new \DateTime(0-2-0));
        $product->setDescription('Ergonomic and stylish!');


        // relates this product to the category
        $product->setCategory($category);

        $entityManager = $doctrine->getManager();
        $entityManager->persist($category);
        $entityManager->persist($product);
        $entityManager->flush();

        return new Response(
            'Saved new product with id: '.$product->getId()
            .' and new category with id: '.$category->getId()
        );
    }
    #[Route('/product/productByCat/{id}', name: 'productByCat')]
    public  function productByCatAction(ManagerRegistry $doctrine ,$id):Response
    {
        $category = $doctrine->getRepository(Category::class)->find($id);
        $products = $category->getProducts();
        $categories = $doctrine->getRepository('App:Category')->findAll();
        return $this->render('product/index.html.twig', ['products' => $products,
            'categories'=>$categories]);
    }
    #[Route('/product/details/{id}', name: 'product_details')]
    public  function detailsAction(ManagerRegistry $doctrine ,$id)
    {
        $products = $doctrine->getRepository('App:Product')->find($id);

        return $this->render('product/details.html.twig', ['products' => $products]);
    }
    #[Route('/product/delete/{id}', name: 'product_delete')]
    public function deleteAction(ManagerRegistry $doctrine,$id)
    {
        $em = $doctrine->getManager();
        $product = $em->getRepository('App:Product')->find($id);
        $em->remove($product);
        $em->flush();

        $this->addFlash(
            'error',
            'Product deleted'
        );

        return $this->redirectToRoute('product_list');
    }
    #[Route('/product/create', name: 'product_create', methods:'GET","POST')]
    public function createAction(ManagerRegistry$doctrine,Request $request, SluggerInterface $slugger)
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // uplpad file
            $productImage = $form->get('productImage')->getData();
            if ($productImage) {
                $originalFilename = pathinfo($productImage->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $productImage->guessExtension();

                // Move the file to the directory where brochures are stored
                try {
                    $productImage->move(
                        $this->getParameter('productImages_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash(
                        'error',
                        'Cannot upload'
                    );// ... handle exception if something happens during file upload
                }
                $product->setProductImage($newFilename);
            }else{
                $this->addFlash(
                    'error',
                    'Cannot upload'
                );// ... handle exception if something happens during file upload
            }
            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();

            $this->addFlash(
                'notice',
                'Product Added'
            );
            return $this->redirectToRoute('product_list');
        }
        return $this->renderForm('product/create.html.twig', ['form' => $form,]);
    }

    public function saveChanges(ManagerRegistry $doctrine,$form, $request, $product)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $product->setName($request->request->get('product')['name']);
            $product->setCategory($request->request->get('product')['category']);
            $product->setPrice($request->request->get('product')['price']);
            $product->setPrice($request->request->get('product')['quantity']);
            $product->setDescription($request->request->get('product')['description']);
            $product->setDate(\DateTime::createFromFormat('Y-m-d', $request->request->get('product')['date']));
            $em = $doctrine->getManager();
            $em->persist($product);
            $em->flush();

            return true;
        }
        return false;
    }
    #[Route('/edit/{id}', name: 'editP')]
    public function editAction(ManagerRegistry $doctrine, $id, Request $request)
    {
        //$product = new Product();
        $em = $doctrine->getManager();
        $product = $em->getRepository('App:Product')->find($id);
        $categories = $em -> getRepository('App:Category') -> findAll();
        $form = $this ->createForm( ProductType::class, $product);
        $form ->handleRequest($request);

        if ($form ->isSubmitted()){
            $entityManager = $doctrine ->getManager();
            $entityManager -> persist($product);
            $entityManager -> flush();

           return $this -> redirectToRoute('product_list');
        }

        return $this->render('product/edit.html.twig', [
            'form' => $form->createView(),
            'categories ' => $categories
        ]);
    }

}
