<?php

namespace Dontdrinkandroot\SymfonyAngularRestExample\WebBundle\Controller;

use Dontdrinkandroot\SymfonyAngularRestExample\BaseBundle\Entity\BlogPost;
use Dontdrinkandroot\SymfonyAngularRestExample\BaseBundle\Entity\Comment;
use Dontdrinkandroot\SymfonyAngularRestExample\BaseBundle\Form\BlogPostType;
use Dontdrinkandroot\SymfonyAngularRestExample\BaseBundle\Form\CommentType;
use Dontdrinkandroot\SymfonyAngularRestExample\BaseBundle\Repository\BlogPostRepository;
use Dontdrinkandroot\SymfonyAngularRestExample\BaseBundle\Service\ContainerServicesTrait;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class BlogPostController extends BaseController
{
    public function detailAction(Request $request, $id)
    {
        $blogPost = $this->getBlogPostService()->loadBlogPost($id);

        $commentForm = $this->createForm(CommentType::class);
        $commentForm->handleRequest($request);
        if ($commentForm->isValid()) {
            if (!$this->isGranted('ROLE_USER')) {
                throw new AccessDeniedException();
            }
            /** @var Comment $comment */
            $comment = $commentForm->getData();
            $comment->setBlogPost($blogPost);
            $comment->setAuthor($this->getUser());
            $comment->setDate(new \DateTime());
            $this->getBlogPostService()->saveComment($comment);
        }

        $comments = $this->getBlogPostService()->listCommentsByBlogPost($id);

        return $this->render(
            '@DdrSymfonyAngularRestExampleWeb/BlogPost/detail.html.twig',
            ['blogPost' => $blogPost, 'comments' => $comments, 'commentForm' => $commentForm->createView()]
        );
    }

    public function editAction(Request $request, $id)
    {
        $blogPost = null;
        if ('create' === $id) {
            $blogPost = new BlogPost();
            $blogPost->setAuthor($this->getUser());
        } else {
            $blogPost = $this->getBlogPostService()->loadBlogPost($id);
            if (!$this->isGranted('ROLE_ADMIN') || !$blogPost->getAuthor()->getId() === $this->getUser()->getId()) {
                throw new AccessDeniedException();
            }
        }

        $form = $this->createForm(BlogPostType::class, $blogPost);
        $form->handleRequest($request);
        if ($form->isValid()) {
            /** @var BlogPost $blogPost */
            $blogPost = $form->getData();
            $blogPost->setDate(new \DateTime());
            $this->getBlogPostService()->saveBlogPost($blogPost);

            return $this->redirectToRoute('ddr_example_web_index');
        }

        return $this->render(
            '@DdrSymfonyAngularRestExampleWeb/BlogPost/edit.html.twig',
            ['form' => $form->createView(), 'blogPost' => $blogPost]
        );
    }

    public function deleteAction($id)
    {
        $blogPost = $this->getBlogPostService()->loadBlogPost($id);

        if (!$this->isGranted('ROLE_ADMIN') || !$blogPost->getAuthor()->getId() === $this->getUser()->getId()) {
            throw new AccessDeniedException();
        }

        $this->getBlogPostService()->deleteBlogPost($blogPost);

        return $this->redirectToRoute('ddr_example_web_index');
    }

    public function deleteCommentAction($id)
    {
        $comment = $this->getBlogPostService()->loadComment($id);

        if (!$this->isGranted('ROLE_ADMIN') || !$comment->getAuthor()->getId() === $this->getUser()->getId()) {
            throw new AccessDeniedException();
        }

        $this->getBlogPostService()->deleteComment($comment);

        return $this->redirectToRoute(
            'ddr_example_web_blogpost_detail',
            ['id' => $comment->getBlogPost()->getId()]
        );
    }
}
