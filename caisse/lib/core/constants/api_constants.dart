class ApiConstants {
  static const String baseUrl = 'https://www.fsdyayra.be/api/v1';
  static const int connectTimeout = 30000;
  static const int receiveTimeout = 30000;

  // Auth
  static const String login = '/auth/login';
  static const String me = '/auth/me';
  static const String logout = '/auth/logout';

  // Agent & Cashier Operations
  static const String agentDashboard = '/agent/dashboard';
  static const String clients = '/agent/clients';
  static const String clientSearch = '/agent/clients/search';
  static const String accounts = '/agent/accounts';
  static const String quickDeposit = '/agent/quick/deposit';
  static const String quickWithdrawal = '/agent/quick/withdrawal';
  static const String quickTransfer = '/agent/quick/transfer';
  static const String searchAccount = '/agent/quick/search-account';
  static const String transactions = '/agent/transactions';
  static const String syncTransactions = '/agent/transactions/sync';
  static String accountTransactions(int accountId) => '/agent/accounts/$accountId/transactions';
  static String approveClientKyc(int clientId) => '/agent/clients/$clientId/approve-kyc';

  // Cashier Session Management
  static const String sessionCurrent = '/agent/session/current';
  static const String sessionOpen = '/agent/session/open';
  static const String sessionClose = '/agent/session/close';
  static const String sessionVaultTransfer = '/agent/session/vault-transfer';
  static const String sessionHistory = '/agent/session/history';
  static String transactionReversal(int transactionId) => '/agent/transactions/$transactionId/reversal';

  // Loans & Credit Management
  static const String loans = '/agent/loans';
  static const String loanConfig = '/agent/loans/config';
  static String clientLoanEligibility(int clientId) => '/agent/loans/client/$clientId/eligibility';
  static const String loanSimulate = '/agent/loans/simulate';
  static const String loanApply = '/agent/loans/apply';
  static String loanApprove(int loanId) => '/agent/loans/$loanId/approve';
  static String loanDisburse(int loanId) => '/agent/loans/$loanId/disburse';
  static String loanRepay(int loanId) => '/agent/loans/$loanId/repay';
  static String loanSchedule(int loanId) => '/agent/loans/$loanId/schedule';

  // Field Agents Supervision & Daily Collections
  static const String fieldAgents = '/agent/field-agents';
  static String fieldAgentDetails(int agentId) => '/agent/field-agents/$agentId';
}

