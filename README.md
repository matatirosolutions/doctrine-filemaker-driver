# Doctrine FileMaker driver #

A Doctrine driver to interact with FileMaker using the CWP API.

## Installation ##

    composer require matatirosoln/doctrine-filemaker-driver
    
For right now you'll need to add `dev-master` to that as there isn't a tagged version yet - expect the first tag in late August 2017 when the first implementation of this goes into a production environment.
    
## Configuration ##
    
In your Doctrine configuration comment out 

    driver: xxxx
and replace it with

    driver_class: MSDev\DoctrineFileMakerDriver\FMDriver
    
## Conventions ##

1. Create a model to represent a FileMaker **layout**. Set the name of the layout as the model table.
    
        /**
         * Keyword
         *
         * @Table(name="Keyword")
         * @Entity(repositoryClass="AppBundle\Repository\KeywordRepository")
         */
         class Keyword
            
2. In your model create an id field which is mapped to the special rec_id pseudo field

        /**
         * @var int
         *
         * @Column(name="rec_id", type="integer")
         */
        private $id;
     
3. Create your 'actual' ID field, as used for relationships, as a separate property of your model. Set its GeneratedValue strategy to be `Custom` which will mean that Doctrie will wait for FM to assign that value - the assumption being that this is an auto-enter calc field (probaby Get(UUID)). You then need to specifiy the CustomIdGenerator and set this to `MSDev\DoctrineFileMakerDriver\FMIdentityGenerator` so that the value is returned as a string.  
   
       /**
        * @var string
        *
        * @Column(name="_id_Keyw", type="string" length=255)
        * @Id
        * @GeneratedValue(strategy="CUSTOM")
        * @CustomIdGenerator(class="MSDev\DoctrineFileMakerDriver\FMIdentityGenerator")
        */
       private $uuid;
       
4. Add other properties as required. To access related fields on your layout enclose the field name in single quotes in the column mapping.
     
         /**
          * @var string
          *
          * @Column(name="'absCon::email'", type="string", length=255)
          */
         private $contactEmail;

## Considerations ##

1. Because of the way in which more 'conventional' databases handle relationships, there is no concept of a portal. To access related data create a corresponding model for that table (layout) and create standard Doctrine relationships (OneToOne, OneToMany, ManyToOne etc).
2. If your model contains caclulation fields you will run into issues when trying to create a new record, since Doctrine will try and set those fields to null. One 'solution' to this is to create a 'stub' of your model which contains only the fields which are necesary to create a new record and to instantiate that for record creation.
 
## See also ##
 
This driver has been developed for use within Symfony applications (because that's what we do). The [Doctrine FileMaker bundle](https://github.com/matatirosolutions/doctrine-filemaker-driver-bundle "Doctrine FileMaker bundle") includes this driver as well as creating services to access scripts, containers, valuelists and more. 