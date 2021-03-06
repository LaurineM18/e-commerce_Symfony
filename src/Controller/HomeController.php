<?php

namespace App\Controller;

use App\Entity\Membre;
use App\Entity\Contact;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Form\ContactType;
use App\Form\ConnexionType;
use App\Form\InscriptionType;
use Doctrine\ORM\EntityManager;
use App\Repository\ContactRepository;
use App\Repository\CommandeRepository;
use App\Security\AppCustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {

        $listeProduit = $em->getRepository(Produit::class)->findAll();

        return $this->render('home/index.html.twig', [
            'produits' => $listeProduit,
        ]);
    }


    #[Route('/inscription', name: 'app_inscription')]
    public function inscription(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $userPasswordHasher,
        AppCustomAuthenticator $formAuthenticator,
        UserAuthenticatorInterface $authenticator
    ): Response {

        if($this->getUser()){
            return $this->redirectToRoute('app_home');
        }

        
        $membre = new Membre();
        $formInscription = $this->createForm(InscriptionType::class , $membre);

        $formInscription->handleRequest($request);

        if ($formInscription->isSubmitted() && $formInscription->isValid()) {
            
            $membre->setPassword(
            $userPasswordHasher->hashPassword(
                    $membre,
                    $formInscription->get('password')->getData()
                )
            );
            $membre->setRoles(["ROLE_MEMBRE"]);

            $em->persist($membre);
            $em->flush();

            return $authenticator->authenticateUser(
                $membre,
                $formAuthenticator,
                $request
            );
        }

        return $this->render('security/inscription.html.twig', [
            "formInscription" => $formInscription->createView(),
        ]);

        
    }

    #[Route('/produit/{id}', name: 'produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('home/detailProduit.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route("/panier/{id}" , name:"add_panier")]
    public function addPanier(Request $request ){

        $idProduitAchete = $request->attributes->get("id");
        $session = $request->getSession();//Je r??cup??re la session
       
        //Si je n'ai pas une variable panier dans session, on cr??e
        /*if(!$session->has("panier")){
            $session->set("panier", []);
        }*/
        $panier = $session->get("panier", []); //Cr??er $_SESSION["panier]
        
        //Ajouter des produits 
        if(!empty($panier[$idProduitAchete])){
            $panier[$idProduitAchete]++ ;
        }else {
            $panier[$idProduitAchete] = 1 ;
        }
        $session->set("panier", $panier);//permet de cumuler les diff??rentes valeurs dans la session
        
        //dd($session->get("panier"));

        return $this->redirectToRoute("panier");
        
    }

    #[Route("/panier" , name:"panier")]
    public function panier(EntityManagerInterface $em , Request $request ){

        $session = $request->getSession();//Je r??cup??re la session
        $panier = $session->get("panier");
        
        if(empty($panier)){
            $panier = $session->get("panier", []);
        };
    
        $produitsPanier = [];

        //Pour chaque produit dans le panier, on stocke la quantit?? et l'objet produit
        foreach($panier as $id => $qte){
            $produitsPanier[] =[
                "qte" => $qte,
                "produit" => $em->getRepository(Produit::class)->find($id)
            ];
            
        }
        
        //On d??finit le montant total du panier ?? 0
        $totalPanier = 0;

        $nbProduits = count($produitsPanier);
        //Pour chaque produit dans le panier, le total ??quivaut ?? la quantit?? * le prix
        foreach($panier as $id => $qte){
            $totalPanier += $qte * $em->getRepository(Produit::class)->find($id)->getPrix();
            
        }
        
        return $this->render("panier/index.html.twig" , compact("produitsPanier", "totalPanier", "nbProduits"));

    }

    #[Route("/supprimer_produit/{id}" , name:"supprimer_produit")]
    public function supprimerQuantiteProduit(Request $request ){

        $idProduitAchete = $request->attributes->get("id");
        $session = $request->getSession();
        $panier = $session->get("panier");

        //Si le produit existe dans le panier
        if(!empty($panier[$idProduitAchete])){
            //Si la quantit?? du produit est sup??rieure ?? 1
            if($panier[$idProduitAchete] > 1){
                //On retire 1 quantit??
                $panier[$idProduitAchete]-- ;
            }else {
                //Sinon on supprime le produit du panier
                unset($panier[$idProduitAchete]) ;
            }
        }
        
        //Je mets ?? jour le tableau 'panier' avec les nouvelles valeurs
        $session->set('panier', $panier);

        
        return $this->redirectToRoute("panier");

    }

    #[Route("/ajouter_produit/{id}" , name:"ajouter_produit")]
    public function ajouterQuantiteProduit(Request $request ){

        $idProduitAchete = $request->attributes->get("id");
        $session = $request->getSession();
        $panier = $session->get("panier");
       
        $panier[$idProduitAchete]++ ;

        //Je mets ?? jour le tableau 'panier' avec les nouvelles valeurs
        $session->set('panier', $panier);
        
        return $this->redirectToRoute("panier");

    }
    
    #[Route("/supprimer_panier/{id}" , name:"supprimer_panier")]
    public function supprimerPanier(Request $request){
        //R??cup??ration de l'id du produit ?? supprimer
        $idProduitAchete = $request->attributes->get("id");
        //On r??cup??re la session
        $session = $request->getSession();
        //On r??cup??re le panier
        $panier = $session->get("panier");

        //Supprimer le produit avec cet id
        unset($panier[$idProduitAchete]);

        $session->set('panier', $panier);

        return $this->redirectToRoute("panier");

    }

    #[Route("/vider_panier" , name:"vider_panier")]
    public function viderPanier(Request $request){
        //On r??cup??re la session
        $session = $request->getSession();
        //On r??cup??re le panier
        $session->get("panier");

        //Supprimer le panier
        $session->remove("panier");

        return $this->redirectToRoute("panier");

    }
   
    //Pr??sentation de la liste des commandes de l'utilisateur
    #[Route("/compte", name: "compte")]
    public function compte(EntityManagerInterface $em)
    {
        $commandes = $em->getRepository(Commande::class)->findBy(['membre' => $this->getUser()]);
        $commandesTriees = array_reverse($commandes);

        return $this->render('compte/index.html.twig', [
            'membre' => $commandes[0]->getMembre()->getPrenom(),
            'commandes' => $commandesTriees        ]);
    }
    

    //Achats des produits (faire une commande)
    #[Route("/commande_achat" , name:"commande_achat")]
    public function commandeAchat(Request $request, EntityManagerInterface $em){
        //On v??rifie si l'utilisateur est connect?? pour passer la commande
        if(!$this->getUser()){
            //Renvoie vers la page de connexion
            return $this->redirectToRoute('app_login');
        }
        
        //On r??cup??re la session
        $session = $request->getSession();
        $panier = $session->get("panier");
        
        //On d??finit un tableau des produits du panier
        $produitsPanier = [];
        
        $i=0;
        //Pour chaque produit dans le panier, on ajoute au tableau la quantit?? et l'objet produit
        foreach($panier as $id => $qte){
            $produitsPanier[] =[
                "qte" => $qte,
                "produit" => $em->getRepository(Produit::class)->find($id)
            ];
                //Cr??ation d'une nouvelle commande         
                $commande = new Commande();
                
                //Mettre les donn??es dans la commande 
                $commande->setMembre($this->getUser())
                ->setMontant($produitsPanier[$i]["produit"]->getPrix()*$produitsPanier[$i]["qte"])
                ->setQuantite($produitsPanier[$i]["qte"])
                ->setProduit($produitsPanier[$i]["produit"])
                ->setEtat("En cours de traitement");
                
                //Envoyer en BDD
                $em->persist($commande);
                $em->flush();

            $i++;
            
        }
        //Supprimer les produits du panier
        $panier = $session->set("panier", []);
        
        
        return $this->redirectToRoute("compte");
    }


    #[Route("/collection/{collection}" , name:"collection")]
    public function collection(EntityManagerInterface $em, Produit $produit){
        $collection = $produit->getCollection();
        $produitsCollection = $em->getRepository(Produit::class)->findBy(['collection' => $produit->getCollection()]);

        return $this->render('home/collection.html.twig', [
            'produitsCollection' => $produitsCollection,
            'collection' => $collection
        ]);
    }

    #[Route('/contact', name: 'app_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, ContactRepository $contactRepository): Response
    {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactRepository->add($contact, true);
            
            $this->addFlash('success', "Votre message a bien ??t?? envoy??");

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }


        return $this->renderForm('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }

    #[Route('/contact/membre', name: 'app_contact_membre', methods: ['GET', 'POST'])]
    public function contactMembre(Request $request, ContactRepository $contactRepository): Response
    {
        $membre = $this->getUser();

        $contact = new Contact();
        $contact->setPrenom($membre->getPrenom())
        ->setNom($membre->getNom())
        ->setEmail($membre->getEmail());

        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $contactRepository->add($contact, true);
            
            $this->addFlash('success', "Votre message a bien ??t?? envoy??");

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }


        return $this->renderForm('contact/new.html.twig', [
            'contact' => $contact,
            'form' => $form,
        ]);
    }


    


}
