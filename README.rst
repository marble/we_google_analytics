we_google_analytics
===================

2012-07-13
  Use different code as described here:
  http://support.google.com/googleanalytics/bin/answer.py?hl=de&answer=174090
  With the old code the automatic authorization in the webmaster tools did not 
  work. It it works with the new code has still be to confirmed.
  Now, new::

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();

  Instead of, old code::

    (function(d, t) {
     var g = d.createElement(t); g.async = true;
     g.src = ('https:' == d.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
     var s = d.getElementsByTagName(t)[0]; s.parentNode.insertBefore(g, s);
    })(document, 'script');
  

2012-02-29
  See http://forge.typo3.org/issues/34421

  and email ``Feature_request_with_PATCH__A_modified_EXT_we_google_analytics_with_extended_functionality.eml``


Originally:
  Created from TYPO3 extension we_google_analytics 1.2.1


End of README.rst