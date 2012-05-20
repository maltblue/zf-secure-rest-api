#Purpose/Overview
The aim of the repository is to provide a basic implementation, using Zend Framework, to create a secure REST service following, in concept, the Amazon S3 approach. It's not perfect, but it's more of a teaching tool. So please bear that in mind.

#Requirements
To use the code, you need to do a few things:
1. Have a database supported by Zend_Db available and configure the connection to it in the /application/configs/application.ini file. You can use the default SQL file for MySQL that's supplied in the /data/sql directory.
2. Generate a request hash based on your request
3. Supply the two custom headers when calling the app.

###Generating a Hash
To generate a hash to send, use the following PHP example. It uses the SHA1 algorithm, seeds with a made up secret key, please provide your own and a fictitious request, which is based on the poll route in the /application/configs/application.ini file.

<code>
    print hash_hmac(
        'sha1', 
        implode(',', array('datatype' => 'userdata', 'datafield' => 'firstname')), 
        'vog4muf6cav2lyt8f', 
        FALSE
    );
</code>

##Supplying the Custom Headers
To call the service, you have to authenticate with it, which requires two headers: 
 * CommonApikey - You're API key for the service
 * CommonRequestHash - A hash of the request you're making, seeded with your secret key

If either one or both of these is not present, then the request will return "Unauthorised request". If the hash or API key is inaccurate then the same message will also be returned. 

NB: The module, controller and action parameters, if specified are not included when the application generates a hash to compare against the one provided.

###Calling the Application
To use the class, take the following curl request, based on the CommonApikey, CommonRequestHash and generated hash listed above:

curl --verbose --header "CommonApikey: oks5ath9lid6nil3n" --header "CommonRequestHash: 2d347849ca782c03d74ec9bc2a3bc674c62dfec8" http://zf-srest-api.localdomain/poll/userdata/firstname/

#Output
You should see the following output when successfully run:
`
    <style>
        a:link,
        a:visited
        {
            color: #0398CA;
        }
        span#zf-name
        {
            color: #91BE3F;
        }
        div#welcome
        {
            color: #FFFFFF;
            background-image: url(http://framework.zend.com/images/bkg_header.jpg);
            width:  600px;
            height: 400px;
            border: 2px solid #444444;
            overflow: hidden;
            text-align: center;
        }
        div#more-information
        {
            background-image: url(http://framework.zend.com/images/bkg_body-bottom.gif);
            height: 100%;
        }
    </style>
    <div id="welcome">
        <h1>Welcome to the <span id="zf-name">Zend Framework!</span></h1>
        <h3>This is your project's main page</h3>
        <div id="more-information">
            <p><img src="http://framework.zend.com/images/PoweredBy_ZF_4LightBG.png" /></p>
            <p>
                Helpful Links: <br />
                <a href="http://framework.zend.com/">Zend Framework Website</a> |
                <a href="http://framework.zend.com/manual/en/">Zend Framework Manual</a>
            </p>
        </div>
    * Connection #0 to host zf-secure-rest-api.localdomain left intact
    * Closing connection #0
`

#Updates
The readme will further explain the application in the next update.