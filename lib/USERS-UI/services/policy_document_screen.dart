import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class PolicyDocumentScreen extends StatelessWidget {
  final String title;
  final PolicyDocument document;

  const PolicyDocumentScreen({
    super.key,
    required this.title,
    required this.document,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Colors.black),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          title,
          style: GoogleFonts.poppins(
            color: Colors.black,
            fontSize: 18,
            fontWeight: FontWeight.w600,
          ),
        ),
        centerTitle: false,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(
            color: Colors.grey.shade200,
            height: 1,
          ),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: document.sections.map((section) {
            return _buildSection(section);
          }).toList(),
        ),
      ),
    );
  }

  Widget _buildSection(PolicySection section) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (section.title.isNotEmpty) ...[
            Text(
              section.title,
              style: GoogleFonts.poppins(
                fontSize: 18,
                fontWeight: FontWeight.w700,
                color: Colors.black87,
                height: 1.4,
              ),
            ),
            const SizedBox(height: 12),
          ],
          ...section.content.map((content) {
            if (content.isBold) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: Text(
                  content.text,
                  style: GoogleFonts.poppins(
                    fontSize: 15,
                    fontWeight: FontWeight.w600,
                    color: Colors.black87,
                    height: 1.6,
                  ),
                ),
              );
            } else {
              return Padding(
                padding: const EdgeInsets.only(bottom: 16),
                child: Text(
                  content.text,
                  style: GoogleFonts.poppins(
                    fontSize: 14,
                    fontWeight: FontWeight.w400,
                    color: Colors.grey.shade700,
                    height: 1.7,
                  ),
                ),
              );
            }
          }).toList(),
        ],
      ),
    );
  }
}

// Data Models
class PolicyDocument {
  final List<PolicySection> sections;

  PolicyDocument({required this.sections});
}

class PolicySection {
  final String title;
  final List<PolicyContent> content;

  PolicySection({
    required this.title,
    required this.content,
  });
}

class PolicyContent {
  final String text;
  final bool isBold;

  PolicyContent({
    required this.text,
    this.isBold = false,
  });
}

// Policy Documents Data
class CargoDocuments {
  static PolicyDocument get privacyPolicy => PolicyDocument(
    sections: [
      PolicySection(
        title: '',
        content: [
          PolicyContent(
            text: 'DOON Transport Technologies. ("DOON", "we", or "us") is committed to protecting and respecting your privacy.',
            isBold: true,
          ),
          PolicyContent(
            text: '1. This policy sets out the basis on which we will use, disclose or otherwise process any personal data that we collect from you or that you upload, in conjunction with our terms of use and any other relevant documents. Please read the following carefully to understand our views and practices regarding your data and how we will treat it.',
          ),
          PolicyContent(
            text: '2. By accepting our Terms of Use, you consent to the collection, use, disclosure and transfer of your Data in the manner and for the purposes set out below.',
          ),
          PolicyContent(
            text: '3. Unless otherwise provided, defined terms in this privacy policy shall have the same meaning as in our Terms of Use.',
          ),
        ],
      ),
      PolicySection(
        title: 'Information We May Collect',
        content: [
          PolicyContent(
            text: '4. We may collect and process the following data from you:',
          ),
          PolicyContent(
            text: 'a. information that you provide by filling in forms on our Platform, including information provided at the time of registering for an account on our Platform, subscribing to any services provided by us, posting material, reporting a problem with our Platform, or requesting further services;',
          ),
          PolicyContent(
            text: 'b. documents or images that you upload onto our Platform;',
          ),
          PolicyContent(
            text: 'c. details of transactions you carry out through our Platform;',
          ),
          PolicyContent(
            text: 'd. details of your visits to our Platform and the resources that you access;',
          ),
          PolicyContent(
            text: 'e. information collected whenever you use the Platform or leave the Platform running on any device;',
          ),
          PolicyContent(
            text: 'f. if you contact us, a record of that correspondence; and',
          ),
          PolicyContent(
            text: 'g. payment details or other financial details that you upload onto our Platform to facilitate your transactions.',
          ),
        ],
      ),
      PolicySection(
        title: 'IP Address',
        content: [
          PolicyContent(
            text: '5. We may collect and process information about your computer, including your IP address, operating system, and browser type. This is for system administration and to report aggregate information to our advertisers. This data is statistical and does not identify any individual user or their browsing actions and patterns.',
          ),
        ],
      ),
      PolicySection(
        title: 'Cookies',
        content: [
          PolicyContent(
            text: '6. Our platform uses cookies to distinguish you from other users of our platform. This helps us provide you with a good browsing experience and also allows us to improve our platform.',
          ),
        ],
      ),
      PolicySection(
        title: 'Where We Store Your Data',
        content: [
          PolicyContent(
            text: '7. The data we collect from you may be transferred to and stored at a destination outside of the Philippines. It may also be processed by staff operating outside of the Philippines who work for us or for any of our third-party service providers. Such staff may be engaged in, among other things, fulfilling your ordered services, processing your payment details, and providing support services. By submitting your data, you agree to this transfer, storage, or processing. We will take all necessary steps to ensure that your data is treated securely and in accordance with this privacy policy.',
          ),
          PolicyContent(
            text: '8. All information you provide to us is stored on our secure servers. If DOON has given you (or you have chosen) a password that enables you to access certain parts of our platform, you are responsible for keeping this password confidential. Please do not share your password with anyone.',
          ),
          PolicyContent(
            text: '9. Unfortunately, the transmission of information via the internet is not completely secure. Although we will do our best to protect your data, DOON cannot guarantee the security of your data transmitted to our platform. Any transmission is at your own risk. Once we have received your information, DOON will use strict procedures and security features to try to prevent unauthorized access.',
          ),
          PolicyContent(
            text: '10. We may collect, use, and process your data for any of the following purposes:',
          ),
          PolicyContent(
            text: 'a. to verify your identity and conduct any necessary checks as we deem fit before registering your account or providing any services to you;',
          ),
          PolicyContent(
            text: 'b. to obtain further background information about you and/or your vehicle, we may access vehicle logs, check the status of your driving license, driver improvement points, outstanding traffic offenses, payment of fines, and other relevant information;',
          ),
          PolicyContent(
            text: 'c. to ensure that content from our platform is presented in the most effective manner for you and your computer;',
          ),
          PolicyContent(
            text: 'd. to provide you with information, products, or services that you request from us and to fulfill our obligations arising from any contracts between you and us;',
          ),
          PolicyContent(
            text: 'e. to send you direct marketing and promotional communications, as well as information on any offers and/or promotions, only if you have given your consent or if we may do so under applicable law;',
          ),
          PolicyContent(
            text: 'f. to allow you to participate in interactive features of our services, when you choose to do so.',
          ),
          PolicyContent(
            text: 'g. to respond to, handle, and process your queries, requests, applications, complaints, and feedback.',
          ),
          PolicyContent(
            text: 'h. to notify you about changes to our services.',
          ),
          PolicyContent(
            text: 'i. to process payment or credit transactions.',
          ),
          PolicyContent(
            text: 'j. to comply with any applicable laws, regulations, codes of practice, guidelines, or rules, or to assist in law enforcement and investigations conducted by any governmental and/or regulatory authority.',
          ),
          PolicyContent(
            text: 'k. to transmit your information to any unaffiliated third parties, including our third-party service providers and agents, and relevant governmental and/or regulatory authorities, whether in the Philippines or abroad, for the purposes stated above.',
          ),
          PolicyContent(
            text: 'l. Any other incidental business purposes related to or in connection with the above.',
          ),
        ],
      ),
      PolicySection(
        title: 'Disclosure of Your Information',
        content: [
          PolicyContent(
            text: '11. We may disclose your data to other users to facilitate your use of our Platform and Services',
          ),
          PolicyContent(
            text: '12. We may disclose your Data to any member of our group, which means our subsidiaries, holding company, our ultimate holding company and its subsidiaries to facilitate our business operation and administration.',
          ),
          PolicyContent(
            text: '13. We may disclose your data to third parties:',
          ),
          PolicyContent(
            text: 'a. for the purposes of providing products or services that you request from us, fulfilling our obligations arising from any contracts entered into between you and us;',
          ),
          PolicyContent(
            text: 'b. in the event that we sell or buy any business or assets, in which case we may disclose your data to the prospective seller or buyer of such business or assets;',
          ),
          PolicyContent(
            text: 'c. we or substantially all of our shares or assets are acquired by a third party, in which case personal data held by us about our customers will be one of the transferred assets;',
          ),
          PolicyContent(
            text: 'd. if DOON is under a duty to disclose or share your personal data, in order to comply with any legal obligation (including any direction from a governmental or regulatory body or law enforcement) or in order to enforce or apply our Terms of Use; or',
          ),
          PolicyContent(
            text: 'e. in an emergency concerning your health and/or safety for the purposes of dealing with that emergency.',
          ),
        ],
      ),
      PolicySection(
        title: 'Your Rights',
        content: [
          PolicyContent(
            text: '14. Our platform may contain links to and from the websites of our partner networks, advertisers, and affiliates. Please note that these websites have their own privacy policies, and we do not accept any responsibility or liability for them. Before submitting any data to these websites, please check their privacy policies.',
          ),
        ],
      ),
      PolicySection(
        title: 'Consent',
        content: [
          PolicyContent(
            text: '15. By providing your data to us, you consent to its collection, use, and disclosure by us for the purposes set out in this privacy policy ("Purposes").',
          ),
          PolicyContent(
            text: '16. Please ensure that you obtain consent and speak to others before providing their data to us. Kindly inform them that we will only collect, use, and disclose their data for the stated purposes. By providing such information, you represent and warrant that the person whose data you have provided consents to its collection, use, and disclosure for these purposes.',
          ),
          PolicyContent(
            text: '17. You have the right to withdraw your consent and request that we stop using and/or disclosing your data for any or all of the purposes stated above. To do so, submit a written request to DOON. However, please be aware that withdrawing your consent may impact our ability to proceed with your transactions, agreements, or interactions with us. Before you make the decision to withdraw your consent, we will inform you of the consequences that may arise. Please note that withdrawing your consent will not prevent DOON from exercising our legal rights (including any remedies or taking any steps we may be entitled to under the law).',
          ),
        ],
      ),
      PolicySection(
        title: 'Access, Update and Deletion',
        content: [
          PolicyContent(
            text: '18. Republic Act No. 10173 gives you the right to access your Data. Your right of access can be exercised in accordance with the Republic Act No. 10173. Any access request may be subject to an administrative fee at our rates then in force to meet our costs in providing you with details of the information we hold about you.',
          ),
          PolicyContent(
            text: '19. In the event that you wish to correct and/or update your data in our records, you may inform DOON through email (compliance@doon.ph). In certain cases, data may also be corrected or updated via the Platform.',
          ),
          PolicyContent(
            text: '20. For data deletion requests, you may inform DOON through email (compliance@doon.ph). To serve the best interest of our users, DOON requires user account information to remain on our platform. Any data deletion may lead to account termination.',
          ),
          PolicyContent(
            text: '21. DOON will respond to requests for access, correction or deletion as soon as reasonably possible. If we are unable to respond to your request within thirty (30) days of receiving it, we will inform you in writing of the time by which we will be able to respond. If we are unable to provide you with any personal data or make a correction as requested by you, we will generally inform you of the reasons why (except where not required to do so under the Republic Act No. 10173).',
          ),
        ],
      ),
      PolicySection(
        title: 'Retention of Privacy Policy',
        content: [
          PolicyContent(
            text: '22. DOON may retain your data for as long as necessary to fulfill the purpose for which it was collected, or as required or permitted by applicable laws. DOON will stop retaining your data, or remove the means by which the data can be associated with you, as soon as it is reasonable to assume that such retention no longer serves the purpose for which the data was collected and is no longer necessary for legal or business purposes.',
          ),
          PolicyContent(
            text: '23. Please note that there is still a possibility that your data may be retained by third parties (e.g. other users of the platform) through various means (e.g. photos, screen captures). DOON does not authorize the retention of your data for purposes unrelated to the use of the platform, or when such data no longer serves the purpose for which it was collected or is no longer necessary for legal or business purposes ("Unauthorized Uses"). To the fullest extent permitted by applicable law, DOON shall not be liable for the retention of your data by third parties for Unauthorized Uses.',
          ),
        ],
      ),
      PolicySection(
        title: 'Changes to our Privacy Policy',
        content: [
          PolicyContent(
            text: '24. Any changes we may make to our privacy policy in the future will be posted on this page and, where appropriate, notified to you by us.',
          ),
        ],
      ),
      PolicySection(
        title: 'Contact',
        content: [
          PolicyContent(
            text: '25. Questions, comments and requests regarding this privacy policy are welcomed and should be addressed to compliance@doon.ph',
          ),
        ],
      ),
    ],
  );

  static PolicyDocument get platformUserAgreement => PolicyDocument(
    sections: [
      PolicySection(
        title: 'Terms of Service',
        content: [
          PolicyContent(
            text: 'Welcome to Cargo - a peer-to-peer car rental platform.',
            isBold: true,
          ),
          PolicyContent(
            text: 'These Terms of Service govern your use of the Cargo platform and services. By accessing or using our platform, you agree to be bound by these terms.',
          ),
        ],
      ),
      PolicySection(
        title: '1. Platform Overview',
        content: [
          PolicyContent(
            text: 'Cargo operates as a marketplace connecting vehicle owners ("Hosts") with individuals seeking to rent vehicles ("Guests"). We facilitate these transactions but are not a party to the rental agreements between Hosts and Guests.',
          ),
        ],
      ),
      PolicySection(
        title: '2. User Eligibility',
        content: [
          PolicyContent(
            text: 'To use Cargo, you must:',
          ),
          PolicyContent(
            text: '• Be at least 18 years of age',
          ),
          PolicyContent(
            text: '• Possess a valid government-issued ID',
          ),
          PolicyContent(
            text: '• Complete identity verification including selfie authentication',
          ),
          PolicyContent(
            text: '• Provide accurate and complete registration information',
          ),
        ],
      ),
      PolicySection(
        title: '3. User Responsibilities',
        content: [
          PolicyContent(
            text: 'Guests agree to:',
          ),
          PolicyContent(
            text: '• Use rented vehicles responsibly and in accordance with all applicable laws',
          ),
          PolicyContent(
            text: '• Return vehicles in the same condition as received',
          ),
          PolicyContent(
            text: '• Pay all fees, deposits, and charges on time',
          ),
          PolicyContent(
            text: '• Report any accidents or damage immediately',
          ),
          PolicyContent(
            text: '• Maintain valid insurance coverage',
          ),
        ],
      ),
      PolicySection(
        title: '4. Host Responsibilities',
        content: [
          PolicyContent(
            text: 'Hosts agree to:',
          ),
          PolicyContent(
            text: '• Provide accurate vehicle information and photos',
          ),
          PolicyContent(
            text: '• Maintain vehicles in safe, roadworthy condition',
          ),
          PolicyContent(
            text: '• Provide required documentation (OR/CR)',
          ),
          PolicyContent(
            text: '• Respond promptly to booking requests',
          ),
          PolicyContent(
            text: '• Be available for vehicle handover and return',
          ),
        ],
      ),
      PolicySection(
        title: '5. Payments and Fees',
        content: [
          PolicyContent(
            text: 'All payments are processed through GCash. Cargo charges a platform fee of 15-20% on each successful booking. Security deposits are held during the rental period and refunded within 3-5 days after successful return.',
          ),
        ],
      ),
      PolicySection(
        title: '6. Cancellation Policy',
        content: [
          PolicyContent(
            text: 'Cancellation terms vary by Host. Review the specific cancellation policy before booking. Refunds, if applicable, are processed within 5-7 business days.',
          ),
        ],
      ),
      PolicySection(
        title: '7. Insurance and Liability',
        content: [
          PolicyContent(
            text: 'Insurance coverage varies by Host. Users are responsible for verifying insurance details before booking. Cargo is not liable for damages, accidents, or losses incurred during rentals.',
          ),
        ],
      ),
      PolicySection(
        title: '8. Prohibited Activities',
        content: [
          PolicyContent(
            text: 'Users may not:',
          ),
          PolicyContent(
            text: '• Use vehicles for commercial ride-sharing services',
          ),
          PolicyContent(
            text: '• Participate in racing or competitions',
          ),
          PolicyContent(
            text: '• Smoke in vehicles (unless explicitly permitted)',
          ),
          PolicyContent(
            text: '• Transport pets (unless explicitly permitted)',
          ),
          PolicyContent(
            text: '• Engage in illegal activities',
          ),
          PolicyContent(
            text: '• Sublease or transfer rental agreements',
          ),
        ],
      ),
      PolicySection(
        title: '9. Dispute Resolution',
        content: [
          PolicyContent(
            text: 'In case of disputes between Users, Cargo will provide support and mediation services. However, Users acknowledge that Cargo is not responsible for resolving all disputes and may require Users to seek independent resolution.',
          ),
        ],
      ),
      PolicySection(
        title: '10. Limitation of Liability',
        content: [
          PolicyContent(
            text: 'Cargo provides the platform "as is" and makes no warranties regarding vehicle condition, user conduct, or transaction outcomes. Our liability is limited to the maximum extent permitted by law.',
          ),
        ],
      ),
      PolicySection(
        title: '11. Termination',
        content: [
          PolicyContent(
            text: 'Cargo reserves the right to suspend or terminate accounts that violate these Terms of Service or engage in fraudulent or harmful activities.',
          ),
        ],
      ),
      PolicySection(
        title: '12. Changes to Terms',
        content: [
          PolicyContent(
            text: 'We may update these Terms of Service from time to time. Continued use of the platform after changes constitutes acceptance of the revised terms.',
          ),
        ],
      ),
      PolicySection(
        title: 'Contact Us',
        content: [
          PolicyContent(
            text: 'For questions about these Terms of Service, contact us at support@cargo.ph',
          ),
        ],
      ),
    ],
  );

  static PolicyDocument get keyPolicy => PolicyDocument(
    sections: [
      PolicySection(
        title: 'Key Management Policy',
        content: [
          PolicyContent(
            text: 'Cargo Key Policy outlines the procedures and responsibilities for key management during vehicle rentals.',
            isBold: true,
          ),
        ],
      ),
      PolicySection(
        title: '1. Key Handover Procedure',
        content: [
          PolicyContent(
            text: 'At Check-out:',
          ),
          PolicyContent(
            text: '• Host must physically hand over all vehicle keys to the Guest',
          ),
          PolicyContent(
            text: '• Both parties must document the key handover in the app',
          ),
          PolicyContent(
            text: '• Verify all keys are functional before departure',
          ),
          PolicyContent(
            text: '• Document any spare keys provided',
          ),
        ],
      ),
      PolicySection(
        title: '2. Spare Key Requirements',
        content: [
          PolicyContent(
            text: 'Hosts are encouraged to provide:',
          ),
          PolicyContent(
            text: '• At least one spare key with the vehicle',
          ),
          PolicyContent(
            text: '• Clear instructions on spare key location',
          ),
          PolicyContent(
            text: '• Emergency contact information if spare key is kept separately',
          ),
        ],
      ),
      PolicySection(
        title: '3. Lost Keys',
        content: [
          PolicyContent(
            text: 'If a Guest loses the vehicle keys:',
          ),
          PolicyContent(
            text: '• Immediately notify the Host and Cargo support',
          ),
          PolicyContent(
            text: '• Document the loss through the app',
          ),
          PolicyContent(
            text: '• Guest is responsible for replacement costs',
          ),
          PolicyContent(
            text: '• Host should provide spare key if available',
          ),
          PolicyContent(
            text: '• Security deposit may be used to cover replacement costs',
          ),
        ],
      ),
      PolicySection(
        title: '4. Keys Locked Inside Vehicle',
        content: [
          PolicyContent(
            text: 'If keys are locked inside the vehicle:',
          ),
          PolicyContent(
            text: '• Contact the Host immediately',
          ),
          PolicyContent(
            text: '• Use spare key if available',
          ),
          PolicyContent(
            text: '• If no spare key available, professional locksmith services may be required',
          ),
          PolicyContent(
            text: '• Cost responsibility depends on circumstances (Guest negligence vs. vehicle malfunction)',
          ),
        ],
      ),
      PolicySection(
        title: '5. Key Return Procedure',
        content: [
          PolicyContent(
            text: 'At Check-in (Return):',
          ),
          PolicyContent(
            text: '• Guest must return all keys received at check-out',
          ),
          PolicyContent(
            text: '• Host must verify all keys are returned',
          ),
          PolicyContent(
            text: '• Document key return in the app',
          ),
          PolicyContent(
            text: '• Security deposit release depends on complete key return',
          ),
        ],
      ),
      PolicySection(
        title: '6. Key Duplication',
        content: [
          PolicyContent(
            text: 'Guests are strictly prohibited from:',
          ),
          PolicyContent(
            text: '• Duplicating vehicle keys',
          ),
          PolicyContent(
            text: '• Programming additional key fobs',
          ),
          PolicyContent(
            text: '• Sharing keys with unauthorized persons',
          ),
          PolicyContent(
            text: 'Violation may result in account suspension and legal action.',
          ),
        ],
      ),
      PolicySection(
        title: '7. Emergency Key Access',
        content: [
          PolicyContent(
            text: 'In emergency situations:',
          ),
          PolicyContent(
            text: '• Contact Cargo support immediately',
          ),
          PolicyContent(
            text: '• Cargo support will coordinate with the Host',
          ),
          PolicyContent(
            text: '• Follow provided emergency procedures',
          ),
          PolicyContent(
            text: '• Document all actions taken',
          ),
        ],
      ),
      PolicySection(
        title: 'Contact',
        content: [
          PolicyContent(
            text: 'For key-related issues or questions, contact Cargo support at support@cargo.ph or through the in-app support feature.',
          ),
        ],
      ),
    ],
  );

  static PolicyDocument get vehicleLeaseAgreement => PolicyDocument(
    sections: [
      PolicySection(
        title: 'Vehicle Lease Agreement',
        content: [
          PolicyContent(
            text: 'This Vehicle Lease Agreement ("Agreement") is between the vehicle owner ("Host") and the renter ("Guest") facilitated through the Cargo platform.',
            isBold: true,
          ),
        ],
      ),
      PolicySection(
        title: '1. Vehicle Information',
        content: [
          PolicyContent(
            text: 'The Host agrees to lease the vehicle as described in the booking details, including:',
          ),
          PolicyContent(
            text: '• Make, model, and year',
          ),
          PolicyContent(
            text: '• License plate number',
          ),
          PolicyContent(
            text: '• Current mileage',
          ),
          PolicyContent(
            text: '• Vehicle condition at handover',
          ),
        ],
      ),
      PolicySection(
        title: '2. Rental Period',
        content: [
          PolicyContent(
            text: 'The rental period begins at the agreed check-out time and ends at the agreed check-in time as specified in the booking. Late returns may incur additional charges of 10-15% of the daily rate per hour.',
          ),
        ],
      ),
      PolicySection(
        title: '3. Rental Fee',
        content: [
          PolicyContent(
            text: 'The Guest agrees to pay:',
          ),
          PolicyContent(
            text: '• Base rental fee as specified in the booking',
          ),
          PolicyContent(
            text: '• Security deposit (refundable)',
          ),
          PolicyContent(
            text: '• Any additional fees (delivery, driver, etc.)',
          ),
          PolicyContent(
            text: '• Platform fee (processed by Cargo)',
          ),
        ],
      ),
      PolicySection(
        title: '4. Guest Obligations',
        content: [
          PolicyContent(
            text: 'The Guest agrees to:',
          ),
          PolicyContent(
            text: '• Use the vehicle responsibly and carefully',
          ),
          PolicyContent(
            text: '• Comply with all traffic laws and regulations',
          ),
          PolicyContent(
            text: '• Return the vehicle with the same fuel level',
          ),
          PolicyContent(
            text: '• Not allow unauthorized persons to drive the vehicle',
          ),
          PolicyContent(
            text: '• Not use the vehicle for illegal purposes',
          ),
          PolicyContent(
            text: '• Maintain the vehicle in good condition',
          ),
          PolicyContent(
            text: '• Report any accidents or damage immediately',
          ),
        ],
      ),
      PolicySection(
        title: '5. Host Obligations',
        content: [
          PolicyContent(
            text: 'The Host agrees to:',
          ),
          PolicyContent(
            text: '• Provide a clean, roadworthy vehicle',
          ),
          PolicyContent(
            text: '• Ensure valid registration and insurance',
          ),
          PolicyContent(
            text: '• Provide all necessary documents',
          ),
          PolicyContent(
            text: '• Disclose any vehicle issues or limitations',
          ),
          PolicyContent(
            text: '• Be available for vehicle handover and return',
          ),
        ],
      ),
      PolicySection(
        title: '6. Vehicle Condition',
        content: [
          PolicyContent(
            text: 'Both parties agree to:',
          ),
          PolicyContent(
            text: '• Inspect the vehicle together at check-out',
          ),
          PolicyContent(
            text: '• Document the vehicle condition with photos',
          ),
          PolicyContent(
            text: '• Note any existing damage or issues',
          ),
          PolicyContent(
            text: '• Repeat inspection process at check-in',
          ),
        ],
      ),
      PolicySection(
        title: '7. Damage and Repairs',
        content: [
          PolicyContent(
            text: 'The Guest is responsible for:',
          ),
          PolicyContent(
            text: '• Any damage caused during the rental period',
          ),
          PolicyContent(
            text: '• Repair costs not covered by insurance',
          ),
          PolicyContent(
            text: '• Depreciation due to accident damage',
          ),
          PolicyContent(
            text: 'Security deposit may be used to cover damage costs.',
          ),
        ],
      ),
      PolicySection(
        title: '8. Insurance',
        content: [
          PolicyContent(
            text: 'Insurance coverage as specified in the booking applies. The Guest must:',
          ),
          PolicyContent(
            text: '• Verify insurance details before departure',
          ),
          PolicyContent(
            text: '• Comply with insurance policy requirements',
          ),
          PolicyContent(
            text: '• Report accidents to insurance provider',
          ),
          PolicyContent(
            text: '• Cooperate with insurance claims process',
          ),
        ],
      ),
      PolicySection(
        title: '9. Fuel Policy',
        content: [
          PolicyContent(
            text: 'The Guest must return the vehicle with the same fuel level as at check-out. If returned with less fuel:',
          ),
          PolicyContent(
            text: '• Guest will be charged for the fuel difference',
          ),
          PolicyContent(
            text: '• A refueling service fee may apply',
          ),
          PolicyContent(
            text: '• Charges will be deducted from security deposit',
          ),
        ],
      ),
      PolicySection(
        title: '10. Accidents and Breakdowns',
        content: [
          PolicyContent(
            text: 'In case of accident or breakdown:',
          ),
          PolicyContent(
            text: '• Guest must contact Host and Cargo support immediately',
          ),
          PolicyContent(
            text: '• Document the incident with photos and details',
          ),
          PolicyContent(
            text: '• File necessary police reports',
          ),
          PolicyContent(
            text: '• Follow insurance claim procedures',
          ),
          PolicyContent(
            text: '• Do not admit fault without consulting insurance',
          ),
        ],
      ),
      PolicySection(
        title: '11. Mileage',
        content: [
          PolicyContent(
            text: 'Mileage limits (if any) are specified in the booking. Exceeding agreed mileage may result in additional charges.',
          ),
        ],
      ),
      PolicySection(
        title: '12. Cleaning',
        content: [
          PolicyContent(
            text: 'The Guest must return the vehicle in reasonably clean condition. Excessive dirt or mess may result in cleaning fees of ₱500-₱1,500 deducted from the security deposit.',
          ),
        ],
      ),
      PolicySection(
        title: '13. Smoking and Pets',
        content: [
          PolicyContent(
            text: 'Unless explicitly permitted by the Host:',
          ),
          PolicyContent(
            text: '• No smoking in the vehicle',
          ),
          PolicyContent(
            text: '• No pets in the vehicle',
          ),
          PolicyContent(
            text: 'Violations may result in cleaning fees and penalties.',
          ),
        ],
      ),
      PolicySection(
        title: '14. Geographic Restrictions',
        content: [
          PolicyContent(
            text: 'The Guest must:',
          ),
          PolicyContent(
            text: '• Obtain Host permission for inter-island travel',
          ),
          PolicyContent(
            text: '• Disclose intended travel destinations',
          ),
          PolicyContent(
            text: '• Comply with any geographic restrictions',
          ),
          PolicyContent(
            text: 'Unauthorized travel may void insurance coverage.',
          ),
        ],
      ),
      PolicySection(
        title: '15. Termination',
        content: [
          PolicyContent(
            text: 'Either party may terminate this agreement for:',
          ),
          PolicyContent(
            text: '• Material breach of terms',
          ),
          PolicyContent(
            text: '• Unsafe vehicle conditions',
          ),
          PolicyContent(
            text: '• Fraudulent information',
          ),
          PolicyContent(
            text: '• Illegal activities',
          ),
        ],
      ),
      PolicySection(
        title: '16. Dispute Resolution',
        content: [
          PolicyContent(
            text: 'Any disputes arising from this agreement will be:',
          ),
          PolicyContent(
            text: '• First addressed through Cargo support mediation',
          ),
          PolicyContent(
            text: '• Escalated to formal dispute resolution if necessary',
          ),
          PolicyContent(
            text: '• Subject to Philippine jurisdiction and laws',
          ),
        ],
      ),
      PolicySection(
        title: '17. Security Deposit',
        content: [
          PolicyContent(
            text: 'The security deposit will be:',
          ),
          PolicyContent(
            text: '• Held by Cargo during the rental period',
          ),
          PolicyContent(
            text: '• Used to cover damages, fees, or violations',
          ),
          PolicyContent(
            text: '• Refunded within 3-5 days after successful return',
          ),
          PolicyContent(
            text: '• Subject to deductions for valid claims',
          ),
        ],
      ),
      PolicySection(
        title: '18. Acknowledgment',
        content: [
          PolicyContent(
            text: 'By confirming this booking, both Host and Guest acknowledge that they have read, understood, and agree to all terms of this Vehicle Lease Agreement.',
            isBold: true,
          ),
        ],
      ),
      PolicySection(
        title: 'Contact',
        content: [
          PolicyContent(
            text: 'For questions or concerns about this agreement, contact Cargo support at support@cargo.ph',
          ),
        ],
      ),
    ],
  );
}