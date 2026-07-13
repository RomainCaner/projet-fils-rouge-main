package com.cyna.admin.ui.frames;

import com.cyna.admin.database.DBConnection;
import com.cyna.admin.security.CredentialUtils;
import com.formdev.flatlaf.FlatLightLaf;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;

public class LoginFrame extends JFrame {
    
    private static final long serialVersionUID = 1L;

    // 1 : ATTRIBUTS ET COMPOSANTS DE STRUCTURE
    private CardLayout rightCardLayout;
    private JPanel rightPanelContainer;

    private JTextField emailField;
    private JPasswordField passField;
    
    private String currentSecretTotp;

    // 2 : CONSTRUCTEUR ET INITIALISATION DE L'INTERFACE GLOBAE
    public LoginFrame() {
        setTitle("CYNA - Authentification Système");
        setSize(900, 550);
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setLocationRelativeTo(null);
        
        java.net.URL iconURL = getClass().getResource("/logo.png");
        if (iconURL != null) {
            ImageIcon icon = new ImageIcon(iconURL);
            setIconImage(icon.getImage());
        }
        
        JPanel mainPanel = new JPanel(new GridLayout(1, 2));

        // --- SECTION GAUCHE : Branding & Identité Visuelle ---
        JPanel leftPanel = new JPanel(new GridBagLayout());
        leftPanel.setBackground(new Color(15, 23, 42)); // Couleur CYNA_DARK
        
        JLabel logoLabel = new JLabel("CYNA");
        logoLabel.setFont(new Font("SansSerif", Font.BOLD, 55));
        logoLabel.setForeground(Color.WHITE);
        
        JLabel subLabel = new JLabel("Système d'Information Interne");
        subLabel.setFont(new Font("SansSerif", Font.ITALIC, 18));
        subLabel.setForeground(new Color(148, 163, 184));

        JPanel leftContent = new JPanel();
        leftContent.setOpaque(false);
        leftContent.setLayout(new BoxLayout(leftContent, BoxLayout.Y_AXIS));
        logoLabel.setAlignmentX(Component.CENTER_ALIGNMENT);
        subLabel.setAlignmentX(Component.CENTER_ALIGNMENT);
        leftContent.add(logoLabel);
        leftContent.add(Box.createRigidArea(new Dimension(0, 10)));
        leftContent.add(subLabel);
        
        leftPanel.add(leftContent);

        // --- SECTION DROITE : Conteneur dynamique (CardLayout) ---
        rightCardLayout = new CardLayout();
        rightPanelContainer = new JPanel(rightCardLayout);
        
        rightPanelContainer.add(createLoginStep(), "Login");
        rightPanelContainer.add(create2FAStep(), "2FA");

        mainPanel.add(leftPanel);
        mainPanel.add(rightPanelContainer);
        add(mainPanel);
    }

    // 3 : 1 - VÉRIFICATION DES IDENTIFIANTS (EMAIL / PASSWORD)
    private JPanel createLoginStep() {
        JPanel panel = new JPanel(new GridBagLayout());
        panel.setBackground(Color.WHITE);
        panel.setBorder(new EmptyBorder(40, 40, 40, 40));
        
        GridBagConstraints gbc = new GridBagConstraints();
        gbc.fill = GridBagConstraints.HORIZONTAL;
        gbc.gridx = 0; gbc.insets = new Insets(5, 0, 5, 0);

        JLabel title = new JLabel("Connexion");
        title.setFont(new Font("SansSerif", Font.BOLD, 30));
        gbc.gridy = 0; panel.add(title, gbc);

        JLabel subtitle = new JLabel("Veuillez renseigner vos identifiants d'accès.");
        subtitle.setForeground(Color.GRAY);
        gbc.gridy = 1; gbc.insets = new Insets(0, 0, 30, 0);
        panel.add(subtitle, gbc);

        gbc.insets = new Insets(5, 0, 5, 0);
        gbc.gridy = 2; panel.add(new JLabel("Adresse e-mail :"), gbc);
        emailField = new JTextField();
        gbc.gridy = 3; panel.add(emailField, gbc);

        gbc.gridy = 4; panel.add(new JLabel("Mot de passe :"), gbc);
        passField = new JPasswordField();
        gbc.gridy = 5; panel.add(passField, gbc);

        JButton btnNext = new JButton("Continuer");
        btnNext.setBackground(new Color(37, 99, 235)); // Bleu CYNA
        btnNext.setForeground(Color.WHITE);
        btnNext.setCursor(new Cursor(Cursor.HAND_CURSOR));
        gbc.gridy = 6; gbc.insets = new Insets(30, 0, 10, 0);
        panel.add(btnNext, gbc);

        // Traitement de la première étape de connexion
        btnNext.addActionListener(e -> {
            String email = emailField.getText().trim();
            String password = new String(passField.getPassword());

            if (email.isEmpty() || password.isEmpty()) {
                JOptionPane.showMessageDialog(this, "Veuillez remplir tous les champs.", "Champ requis", JOptionPane.WARNING_MESSAGE);
                return;
            }

            String sql = "SELECT mot_de_passe_hache, totp_actif, secret_totp FROM utilisateurs WHERE email = ? AND role = 'admin'";

            try (Connection conn = DBConnection.getConnection();
                 PreparedStatement pstmt = conn.prepareStatement(sql)) {
                
                pstmt.setString(1, email);
                
                try (ResultSet rs = pstmt.executeQuery()) {
                    if (rs.next()) {
                        String hash = rs.getString("mot_de_passe_hache");
                        // La vérification (avec normalisation des hachages $2y/$2b
                        // issus de PHP) est déléguée à CredentialUtils, couverte par
                        // des tests unitaires.
                        if (CredentialUtils.passwordMatches(password, hash)) {
                            boolean totpActif = rs.getBoolean("totp_actif");
                            
                            if (totpActif) {
                                // Sauvegarde locale temporaire du secret pour l'étape 2
                                currentSecretTotp = rs.getString("secret_totp");
                                rightCardLayout.show(rightPanelContainer, "2FA");
                            } else {
                                // Pas de double facteur configuré : accès direct à l'application
                                new MainFrame().setVisible(true);
                                this.dispose();
                            }
                        } else {
                            JOptionPane.showMessageDialog(this, "Email ou mot de passe incorrect.", "Authentification échouée", JOptionPane.ERROR_MESSAGE);
                        }
                    } else {
                        JOptionPane.showMessageDialog(this, "Email ou mot de passe incorrect.", "Authentification échouée", JOptionPane.ERROR_MESSAGE);
                    }
                }
            } catch (Exception ex) {
                ex.printStackTrace();
                JOptionPane.showMessageDialog(this, "Erreur système : " + ex.getMessage(), "Erreur critique", JOptionPane.ERROR_MESSAGE);
            }
        });
        
        return panel;
    }

    // 4 : 2 - DOUBLE FACTEUR (VALIDATION CODE UNIQUE TOTP)
    private JPanel create2FAStep() {
        JPanel panel = new JPanel(new GridBagLayout());
        panel.setBackground(Color.WHITE);
        panel.setBorder(new EmptyBorder(40, 40, 40, 40));
        
        GridBagConstraints gbc = new GridBagConstraints();
        gbc.fill = GridBagConstraints.HORIZONTAL;
        gbc.gridx = 0; gbc.insets = new Insets(5, 0, 5, 0);

        JLabel title = new JLabel("Vérification 2FA");
        title.setFont(new Font("SansSerif", Font.BOLD, 30));
        gbc.gridy = 0; panel.add(title, gbc);

        JLabel subtitle = new JLabel("Entrez le code de sécurité généré par votre application mobile.");
        subtitle.setForeground(Color.GRAY);
        gbc.gridy = 1; gbc.insets = new Insets(0, 0, 30, 0);
        panel.add(subtitle, gbc);

        gbc.insets = new Insets(5, 0, 5, 0);
        gbc.gridy = 2; panel.add(new JLabel("Code d'authentification :"), gbc);
        JTextField code2FA = new JTextField();
        code2FA.setFont(new Font("Monospaced", Font.BOLD, 20));
        code2FA.setHorizontalAlignment(JTextField.CENTER);
        gbc.gridy = 3; panel.add(code2FA, gbc);

        JButton btnLogin = new JButton("Valider et se connecter");
        btnLogin.setBackground(new Color(16, 185, 129)); // Vert CYNA
        btnLogin.setForeground(Color.WHITE);
        btnLogin.setCursor(new Cursor(Cursor.HAND_CURSOR));
        gbc.gridy = 4; gbc.insets = new Insets(30, 0, 10, 0);
        panel.add(btnLogin, gbc);

        // Traitement de l'envoi du code TOTP
        btnLogin.addActionListener(e -> {
            String codeStr = code2FA.getText().trim();
            
            if (codeStr.isEmpty()) {
                JOptionPane.showMessageDialog(this, "Veuillez entrer le code 2FA.", "Champ requis", JOptionPane.WARNING_MESSAGE);
                return;
            }

            if (currentSecretTotp == null || currentSecretTotp.isEmpty()) {
                JOptionPane.showMessageDialog(this, "Erreur interne : aucun secret 2FA n'est associé à ce compte.", "Erreur", JOptionPane.ERROR_MESSAGE);
                return;
            }

            try {
                int codeInt = Integer.parseInt(codeStr);

                // Confrontation du code saisi avec la clé secrète stockée en base
                if (CredentialUtils.totpMatches(currentSecretTotp, codeInt)) {
                    new MainFrame().setVisible(true);
                    this.dispose();
                } else {
                    JOptionPane.showMessageDialog(this, "Code 2FA invalide ou expiré.", "Échec de validation", JOptionPane.ERROR_MESSAGE);
                }
            } catch (NumberFormatException ex) {
                JOptionPane.showMessageDialog(this, "Le code doit être composé uniquement de 6 chiffres.", "Format incorrect", JOptionPane.ERROR_MESSAGE);
            }
        });
        
        return panel;
    }

    // 5 : POINT D'ENTRÉE DE L'APPLICATION (MAIN)
    public static void main(String[] args) {
        // Initialisation de la charte moderne FlatLaf Light avant le lancement de l'IHM
        FlatLightLaf.setup();
        SwingUtilities.invokeLater(() -> new LoginFrame().setVisible(true));
    }
}