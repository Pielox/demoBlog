<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Category;
use App\Form\ArticleType;
use App\Form\CommentType;
use App\Form\CategoryType;
use App\Entity\Commentaire;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CommentaireRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class BackOfficeController extends AbstractController
{
    # Méthode qui affiche la page Home du backoffice
    #[Route('/admin', name: 'app_admin')]
    public function adminHome(): Response
    {
        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
        ]);
    }

    # Méthode qui affiche la page Home du backoffice
    #[Route('/admin/articles', name: 'app_admin_articles')]
    #[Route('/admin/articles/{id}/remove', name: 'app_admin_articles_remove')]
    public function adminArticle(EntityManagerInterface $manager, ArticleRepository $article, Article $artRemove = null): Response
    {

        $colonnes = $manager->getClassMetadata(Article::class)->getFieldNames();
        // dd($colonnes);

        /*
            Exo : afficher sous forme de tableau HTML l'ensemble des articles stockés en BDD 
            1. Selectionner en BDD l'ensemble de la table 'article' et transmettre le résultat a la méthode render()
            2. Dans les template 'admin_articles.html.twig', mettre en forme l'affichage des articles dans un tableau HTML
            3. Afficher le nom de la catégorie de chaque article
            4. Afficher le nombre de commentaire de chaque article
            5. Prévoir un lien de modification/Suppression pour chaque article
        */
        

        $cell = $article->findAll();
        
        // dd($cell);

        // Traitement suppression article en BDD
        if($artRemove)
        {
            $id = $artRemove->getId();


            $manager->remove($artRemove);
            $manager->flush();

            $this->addFlash('success', "l'article n° $id a été supprimé avec succès");

            return $this->redirectToRoute('app_admin_articles');
        }

        return $this->render('back_office/admin_articles.html.twig', [
            'colonnes' => $colonnes,
            'cell' => $cell
        ]);
    }

    /*
        Exo : création d'une nouvelle méthode permettant d'insérer et modifier 1 article en BDD
        1. Créer une route '/admin/article/add' (name:app_admin_article_add)
        2. Créer la méthode adminArticlesForm()
        3. Créer un nouveau template 'admin_article_form.html.twig'
        4. Importer et créer le formulaire au sein de la méthode adminArticlesForm() (createForm)
        5. Afficher le formulaire sur le template 'admin_article_form.html.twig'
        6. Dans la méthode adminArticlesForm(), réaliser le traitement permettant d'insérer un nouvel article en BDD (persist() / flush())
    */

    #[Route('/admin/article/add', name: 'app_admin_article_add')]
    #[Route('/admin/{id}/edit', name: 'admin_edit')]
    public function adminArticlesForm(Article $article = null, Request $request, EntityManagerInterface $manager, SluggerInterface $slugger): Response
    {
        //  conditon si modif article
        if($article)
        {
            $photoActuelle = $article->getPhoto();
        }

        //  conditon si ajout article
        if(!$article)
        {
            $article = new Article;
        }
        
        $adminArticlesForm = $this->createForm(ArticleType::class, $article);

        $adminArticlesForm->handleRequest($request);

        if($adminArticlesForm->isSubmitted() && $adminArticlesForm->isValid())
        {
            //  conditon si ajout article
            if(!$article->getId())
            {
                $article->setDate(new \DateTime());
                $txt = "enregistré";
            }
            //  conditon si modif article
            else
            {
                $txt = "modifié";
            }

            $photo = $adminArticlesForm->get('photo')->getData();

            //  conditon pour ajout photo
            if($photo)
            {
                $nomOriginePhoto = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
                //dd($nomOriginePhoto);

                // cela est necessaire pour inclure en toute sécurité le nom du fichier dans l'URL
                $secureNomPhoto = $slugger->slug($nomOriginePhoto);

                $nouveauNomFichier = $secureNomPhoto.' - '.uniqid().'.'.$photo->guessExtension();
                // dd($nouveauNomFichier);
                try
                {
                    $photo->move(
                        $this->getParameter('photo_directory'),
                        $nouveauNomFichier
                    );
                }
                catch(FileException $e)
                {

                }

                $article->setPhoto($nouveauNomFichier);
            }

            //  conditon pour modif photo
            else
            {
                if(isset($photoActuelle))
                {
                    $article->setPhoto($photoActuelle);
                }
                else
                {
                    $article->setPhoto(null);
                }
            
        
            }

        $this->addFlash('success', "L'article a été $txt avec succès !");

            $manager->persist($article);

            $manager->flush();

            return $this->redirectToRoute('app_admin_articles');
        }

        return $this->render('back_office/admin_article_form.html.twig',[
            'adminArticlesForm' => $adminArticlesForm->createView(),
            'editMode' => $article->getId(),
            'photoActuelle' => $article->getPhoto()           
        ]);
    }

    /*
        Exo : affichage et suppression catégorie 
        1. Création nouvelle route '/admin/categorie' (name : app_admin_categories)
        2. Création d'une nouvelle méthode adminCategorie()
        3. Création d'un nouveau template 'admin_categorie.html.twig'
        4. Sélectionner les noms des champs/colonnes de la table Category, les transmettre au template et les afficher
        5. Selectionner dans le controller l'ensemble de la table 'category' (findAll) et transmettre au template (render) et les afficher sur le template (Twig), afficher également le nombre d'article lié à chaque catégorie
        6. Prévoir un lien 'modifier et supprimer pour chaque catégorie
        7. Réaliser le traitement permettant de supprimer une catégorie de la BDD
    */

    #[Route('/admin/categorie', name: 'app_admin_categorie')]
    #[Route('/admin/categorie/{id}/remove', name: 'app_admin_categorie_remove')]
    public function adminCategorie(Request $request, CategoryRepository $category, Category $cat = null, EntityManagerInterface $manager): Response
    {       

        $categorys = $category->findAll();


    if($cat)
    {
        // On récupère le titre de la catégorie ayant la suppression pour l'intégrer dans le mossage utilisateur
        $titreCat=$cat->getTitre();

        //  getArticle() retourne tout les articles liés à la catégorie, si le résultat est vide, cela veut dire qu'aucun article n'est lié à la catégorie, on entre dans le IF et on supprime la catégorie
        if($cat->getArticles()->isEmpty())
        {
            $this->addFlash('success', "La catégorie '$titreCat' a été supprimé avec succès.");

            $manager->remove($cat);
            $manager->flush();
        }
        
        else // Sinon, des articles sont encores liés à la catégorie, alors on affiche un message d'erreur
        {
            $this->addFlash('danger',"Impossible de supprimer la catégorie '$titreCat' car des articles y sont toujours associés.");
        }

        return $this->redirectToRoute('app_admin_categorie');
    }

        return $this->render('back_office/admin_categorie.html.twig', [
            'categorys' => $categorys
        ]);
    }


    #[Route('/admin/categorie/add', name: 'app_admin_categorie_add')]
    
    public function adminCategorieForm(Request $request, EntityManagerInterface $manager): Response
    {
        $category = new Category;

        $formCategory = $this->createForm(CategoryType::class, $category);

        $formCategory->handleRequest($request);

        if($formCategory->isSubmitted() && $formCategory->isValid())
        {

            if($category->getId())
                $txt = 'modifié';
            else
                $txt = 'enregistrée';
            
            $manager->persist($category);
            $manager->flush();

            $titreCat = $category->getTitre();

            $this->addFlash('success',"La catégorie '$titreCat' a été enregistrée avec succès.");

            return $this->redirectToRoute('app_admin_categorie');

        } 

        return $this->render('back_office/admin_categorie_form.html.twig', [
            'formCategory' => $formCategory->createView(),
            'editMode' => $category->getId()
        ]);
    }

    
    /*
        Exo : Affichage et suppression des commentaires
        1. Création d'une nouvelle route '/admin/commentaires' (name: app_admin_commentaires)
        2. Création d'une nouvelle méthode adminCommentaires()
        3. Création d'un nouveau template 'admin_commentaire.html.twig'
        4. Selectionner les noms/champs colonne de la table "comment" et les afficher sur le template
        5. Selectionner l'ensemble de la table 'Comment' et afficher les données sous forme de tableau (prévoir un lien modification et suppression sur chaque commentaire)
        6. mettre en place 'dataTable' pour pouvoir filtrer et rechercher des commentaires
        7. Créer une nouvelle Route (sur la même méthode) '/admin/comment/{id}/ remove' (name: app_admin_comment_remove)
        8. Réaliser le traitement permettant de supprimer un commentaire dans la BDD
    */

    #[Route('/admin/commentaires', name: 'app_admin_commentaires')]
    #[Route('/admin/commentaires/{id}/remove', name: 'app_admin_commentaires_remove')]
    public function adminCommentaires(Request $request, EntityManagerInterface $manager, CommentaireRepository $comments, Commentaire $removeComment = null) : Response
    {
        $colonnes = $manager->getClassMetadata(Commentaire::class)->getFieldNames();

        $cell = $comments->findAll();

        if($removeComment)
            {
                $auteur = $removeComment->getAuteur();
    
    
                $manager->remove($removeComment);
                $manager->flush();
    
                $this->addFlash('success', "le commentaire de $auteur a été supprimé avec succès");
    
                return $this->redirectToRoute('app_admin_commentaires');
            }


        return $this->render('back_office/admin_commentaires.html.twig', [
            'colonnes' => $colonnes,
            'cell' => $cell
        ]);
    }

    #[Route('/admin/commentaires/{id}/update', name: 'app_admin_commentaires_update')]
    public function adminCommentairesForm(Commentaire $commentaires, Request $request, EntityManagerInterface $manager) : Response
    {
        $formComment = $this->createForm(CommentType::class, $commentaires, [
            'commentFormFront' => true
        ]);

        $formComment->handleRequest($request);

        if($formComment->isSubmitted() && $formComment->isValid())
        {
            $this->addFlash('success', "Le commentaire a été modifié avec succès !");

            $manager->persist($commentaires);

            $manager->flush();

            return $this->redirectToRoute('app_admin_commentaires');

        }
            
        return $this->render('back_office/admin_commentaires_form.html.twig', [
            'formComment' => $formComment->createView(),
            'editMode' => $commentaires->getId()
        ]);
            
    }   
    
}