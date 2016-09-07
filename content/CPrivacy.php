<?php

class CPrivacy extends Content
{
    public function GetContentId()
    {
        return ContentKey::CONTENT_KEY_PRIVACY;
    }

    public function AuthLevel()
    {
        return LoginStatus::ANY;
    }

    public function Render($userId)
    {
        $this->cacheable = self::CACHEABLE_ALWAYS;
        $this->suppressTips = self::SUPPRESS_TIPS_ALL;

        $this->html =
            Html::Heading('Memofrog Privacy Policy') .
            Html::P(self::GetNavLink('&lt;&nbsp;Back', Session::IsLoggedIn() ? ContentKey::CONTENT_KEY_HOME : ContentKey::CONTENT_KEY_LOGOUT)) .
            Html::Div('legal',
                Html::P('This Privacy Policy, together with the ' . $this->GetNavLink('Terms of Service', ContentKey::CONTENT_KEY_TERMS_OF_SERVICE) . ', governs the manner in which Memofrog collects, uses, maintains and discloses information collected from users (each, a &quot;User&quot; or &quot;you&quot;) of the Memofrog website (&quot;Memofrog&quot; or &quot;the Site&quot;).') .
                Html::SubHeading('Personally Identifiable Information') .
                Html::P("Memofrog may collect personal identification information from you in a variety of ways, including, but not limited to, when users visit our site, register on the site, fill out forms, respond to surveys, and in connection with other activities, services, features or resources Memofrog makes available on the Site. Users may be asked for, as appropriate, name, and email address, or other information. You may, however, visit the Site anonymously. Memofrog will collect personal identification information from Users only if they voluntarily submit such information to us. Users can always refuse to supply personally identification information, except that it may prevent them from engaging in certain Site related activities.") .
                Html::SubHeading('Non-personally Identifiable Information') .
                Html::P("Memofrog may collect non-personal identification information about you whenever you interact with the site. Non-personal identification information may include the browser name, the type of computer and technical information about Users means of connection to our Site, such as the operating system and the Internet service providers utilized and other similar information.") .
                Html::SubHeading('Web Browser Cookies') .
                Html::P("The Site uses &quot;cookies&quot; to enhance the user experience. Web browsers place cookies on the hard drive for record-keeping purposes and sometimes to track information about them. You may choose to set your` web browser to refuse cookies, or to alert you when cookies are being sent. If you do so, you acknowledge and agree that some parts of the Site may not function properly.") .
                Html::SubHeading('How Memofrog Uses Collected Information') .
                Html::P('Memofrog may collect and use your personal information for the following purposes:') .
                Html::UL(
                    Html::LI( 'To run and operate the Site. Memofrog may use your information to display content on the Site correctly.') .
                    Html::LI( 'To improve customer service. Information you provide helps Memofrog respond to your customer service requests and support needs more efficiently.') .
                    Html::LI( 'To personalize the user experience. Memofrog may use aggregated information to understand how our users as a group use the services and resources provided on the site.') .
                    Html::LI( 'To improve the Site. Memofrog may use feedback you provide to improve our products and services.') .
                    Html::LI( 'To run promotions, contests, surveys or to enable other site feature.') .
                    Html::LI( 'To send you information that you agreed to receive about topics Memofrog thinks will be of interest to you.') .
                    Html::LI( 'To send periodic emails. Memofrog may use your email address to send you information and updates pertaining to your accounts. It may also be used to respond to inquiries, questions, and/or other requests.')) .
                Html::SubHeading('How Memofrog Protects Your Information') .
                Html::P('Memofrog adopts appropriate data collection, storage and processing practices and security measures to protect against unauthorized access, alteration, disclosure or destruction of your personal information, username, password, transaction information and data stored on the Site.') .
                Html::SubHeading('Sharing Your Personal Information') .
                Html::P('Memofrog does not sell, trade, or rent users&apos; personal identification information to others. Memofrog may share generic aggregated demographic information not linked to any personal identification information regarding visitors and users with our business partners, trusted affiliates and advertisers for the purposes outlined above. Memofrog may use third party service providers to help us operate the site or administer activities on our behalf, such as sending out newsletters or surveys. Memofrog may share your information with these third parties for those limited purposes provided that you have given us your permission.') .
                /*Html::RenderSubHeading('Electronic Newsletters') .
                Html::WrapTag('p', 'If you decide to opt-in to the site&apos;s mailing list, you will receive emails that may include company news, updates, related product or service information, etc. If at any time the User would like to unsubscribe from receiving future emails, Memofrog will provide unsubscribe instructions. Memofrog may use third party service providers to help us operate our business and the Site or administer activities on our behalf, such as sending out newsletters or surveys. Memofrog may share your information with these third parties for those limited purposes provided that you have given us your permission.') .
                */
                Html::SubHeading('Changes to This Privacy Policy') .
                Html::P('Memofrog has the discretion to update this privacy policy at any time. Memofrog will post a notification on the main page of the site. Memofrog encourages you to frequently check this page for any changes to stay informed about how we are helping to protect the personal information we collect. You acknowledge and agree that it is your responsibility to review this privacy policy periodically and become aware of modifications.') .
                Html::SubHeading('Your Acceptance of These Terms') .
                Html::P('By using Memofrog, you signify your acceptance of this policy. If you do not agree to this policy, please do not use Memofrog. Your continued use of the Site following the posting of changes to this policy will be deemed your acceptance of those changes.') .
                Html::SubHeading('Contacting Memofrog') .
                Html::P('If you have any questions about this Privacy Policy, the practices of this site, or your dealings with this site, please contact us by <a href=\'mailto:privacy@memofrog.com\'>email</a>.') .
                Html::TagWithClass('p', 'parenthetic', 'Revised November 2, 2015'));
    }
}