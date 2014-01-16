<?php

/**
* This class is related to the MODS Scheme for providing metadata.
* Some elements are not included. Additional elements declarations you can 
* read about at http://www.clarin.eu/cmd/
*/

class OAIMetadataFormat_MODS extends OAIMetadataFormat {
        /**
        * @see OAIMetadataFormat#toXml
	*/
	function toXml(&$record, $format = null) {
            error_log("OAIMetadataFormat_MODS: toXml wird aufgerufen");
                           $article =& $record->getData('article');
			   $journal =& $record->getData('journal');
			   $section =& $record->getData('section');
			   $issue =& $record->getData('issue');
	                   $galleys =& $record->getData('galleys');
       

                           $articleId = $article->getArticleId();

                           //date the article was published
			   $datePublished = $article->getDatePublished();
			   if (!$datePublished) $datePublished = $issue->getDatePublished();
			   if ($datePublished) $datePublished = strtotime($datePublished);
														           
			   //date the article was last modified
	    		   $dateLastModified = $article->getLastModified();
    			   $dateLastModified = strtotime($dateLastModified);

						   
			    // Publisher
		            $publishers = $this->stripAssocArray((array) $journal->getTitle(null));
                            
                            // PaperPackage Name for direct Link to the Package
			    $daos    =& DAORegistry::getDAOs();
			    $rpositoryDao  =& $daos['RpositoryDAO'];
		            $filenamePP = htmlspecialchars(Core::cleanVar(strip_tags($rpositoryDao->getPackageName($articleId))));
		       
		       //PID 
		        $pid = $rpositoryDao->getPID($articleId);
		       
		       // woher der filename kommt muss nochmal überprüft werden. In RpositoryDAO war es nicht zu finden.
		       $filename = '';
                       
		       //PackageType
                       $packageType = $this->getPackageType($articleId);

            //$response = 
	    /*"<?xml version=\"1.0\" enconding=\"UTF-8\"?>" .*/
	   $reponse = "<CMD CMDVersion=\"1.1\" xmlns=\"http://www.clarin.eu/cmd/\" \n" . 
            "\txmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.clarin.eu/cmd/  http://catalog.clarin.eu/ds/ComponentRegistry/rest/registry/profiles/clarin.eu:cr1:p_1375880372976/xsd\">" . 
            "\t<Header>\n" . 
	    "\t\t<MdCreator>Mind Research repository CMDI Creator 1.0</MdCreator>\n" .   
	    "\t\t\t<MdCreationDate>" . date('Y-m-d', $dateLastModified) . "</MdCreationDate>\n";     

	      //if there is a PID, return PID. Otherwise return: NOT ISSUED.
	      if ($pid == NULL){
               $respone .= "\t\t\t<MdSelfLink>NOT_ISSUED</MdSelfLink>\n";					 
	      }
	      else{
	       "\t\t\t<MdSelfLink>" . $pid . "</MdSelfLink>\n"; 
	      }
                            
             $response .=    
	    "\t\t\t<MdProfile>clarin.eu:cr1:p_1375880372976</MdProfile>\n" . 
	    "\t\t\t<MdCollectionDisplayName>$publisher</MdCollectionDisplayName>\n" . 
	    "\t</Header>\n" . 
	    "\t<Resources>\n" . 
	    "\t\t<ResourceProxyList>\n" . 
	    "\t\t\t<ResourceProxy id=\"mrr-" . $pid . "-resource\">\n" . 
	    "\t\t\t\t<ResourceType mimetype=\"application/x-gzip\">Resource</ResourceType>\n" . 
	    "\t\t\t\t<ResourceRef>" . htmlspecialchars(Core::cleanVar(PKPRequest::getBaseUrl() . "/Rpository/src/contrib/$filenamePP")) . "</ResourceRef>\n" . 
	    "\t\t\t</ResourceProxy>\n" . 
	    "\t\t</ResourceProxyList>\n" . 
	    "\t\t<JournalFileProxyList/>\n" . 
	    "\t\t<ResourceRelationList/>\n" . 
	    "\t</Resources>\n" . 
	    "\t<Components>\n" . 
	    "\t\t<paperPackage>\n" . 
            "\t\t\t<type>$packageType</type>\n" . 
	    "\t\t</paperPackage>\n" . 
	    "\t</Components>\n" . 
	    "</CMD>\n" . 
	    "<modsCollection xmlns=\"http//www.loc.gov/mods/v3\">\n" . 
            "<mods ID=\"$filename\">" .
	    "\t<titleInfo>\n" . 
	    "\t\t<title>" . htmlspecialchars(Core::cleanVar(strip_tags($article->getArticleTitle()))) . "</title>\n" . 
 	    "\t</titleInfo>\n";
																		                
	// authors 
                foreach ($article->getAuthors() as $author) {
	                $response .= 
			"\t<name type=\"personal\">\n" . 
			"\t\t<namePart type=\"given\">" . htmlspecialchars(Core::cleanVar($author->getFirstName()) . (($s = $author->getMiddleName()) != ''?" $s":'')) . "</namePart>\n" . 
		        "\t\t<namePart type=\"family\">" . htmlspecialchars(Core::cleanVar($author->getLastName())) . "</namePart>\n" .  
			"\t\t<role>\n" . 
			"\t\t\t<roleTerm authority=\"marcrelator\" type=\"text\">author</roleTerm>\n" . 
			"\t\t</role>\n" ;   
			}

       //date published
	 $response .=
	"\t<originInfo>\n" . 
	"\t\t<dateIssued>" . strftime('%Y', $datePublished) . "</dateIssued>\n" .
        "\t</originInfo>\n" .

	"\t<typeOfRessource>text</typeOfResource>\n" . 
	"\t<genre>journal article</genre>\n";
			
	 // Include abstract(s)
         $abstract = htmlspecialchars(Core::cleanVar(strip_tags($article->getArticleAbstract())));
         if (!empty($abstract)) {
         $response .= "\t<abstract>$abstract</abstract>\n";
         }
								
	$response .=
	"\t<identifier type=\"hdl\">$filename</identifier>\n" . 
	"\t<location>\n" . 
	"\t\t<url displayLabel=\"Electronic full text\" access=\"raw object\">" . htmlspecialchars(Core::cleanVar(Request::url($journal->getPath(), 'article', 'view', $article->getBestArticleId()))) . "\" />\n";
	    
																															    
          $response .= "</mods>";

	return $response;
   }                                                                                                                                                              

    function getPackageType($articleId){
      $daos    =& DAORegistry::getDAOs();
      $rpositoryDao  =& $daos['RpositoryDAO'];
      $filename = $rpositoryDao->getPackageName($articleId);
      if(!$filename == NULL){
        return 'legacy_r_package';
      }
      return 'no package uploaded';

    }


}
?>
