<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/{_locale}")
 */
class ProductController extends AbstractController
{
    /**
     * @Route("/product", name="product_index", methods={"GET"})
     */
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    /**
     * Admin Homepage
     * @Route("/admin/",name="admin_homepage")
     */
    public function indexAdmin(ProductRepository $productRepository): Response
    {
        return $this->render('product/index-admin.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

    /**
     * Admin add new product
     * @Route("/admin/product/new", name="product_new", methods={"GET","POST"})
     */
    public function new(Request $request, TranslatorInterface $t): Response
    {
        $entityManager = $this->getDoctrine()->getManager();

        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('upload_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('success', $t->trans('product.added'));
                }

                $product->setImage($newFilename);
            }
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', $t->trans('product.added'));

            return $this->redirectToRoute('admin_homepage', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/new.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/product/{id}", name="product_show", methods={"GET"})
     */
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }

    /**
     * @Route("/admin/product/{id}/edit", name="product_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Product $product, TranslatorInterface $t): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        // dd($form);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', $t->trans('product.updated'));

            return $this->redirectToRoute('admin_homepage', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('product/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/admin/product/{id}", name="product_delete", methods={"POST"})
     */
    public function delete(Request $request, Product $product, TranslatorInterface $t): Response
    {
        if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($product);
            $entityManager->flush();

            $this->addFlash('danger', $t->trans('product.delete'));
        }

        return $this->redirectToRoute('product_index', [], Response::HTTP_SEE_OTHER);
    }
}