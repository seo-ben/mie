import '../entities/dashboard_stats.dart';
import '../../data/models/client_model.dart';
import '../../data/models/account_model.dart';
import '../../data/models/transaction_model.dart';
import '../../data/models/cashier_session_model.dart';
import '../../data/models/loan_model.dart';
import '../../data/models/client_eligibility_model.dart';
import '../../data/models/field_agent_model.dart';

abstract class ICashierRepository {
  Future<DashboardStats> getDashboardStats({
    String? period,
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    int? sessionId,
  });
  Future<CashierSessionModel> getCurrentSession();
  Future<List<Map<String, dynamic>>> getSessionHistory({String? status, DateTime? date});
  Future<CashierSessionModel> openSession({required double openingBalance, String? notes});
  Future<Map<String, dynamic>> closeSession({required double closingBalance, String? notes});
  Future<List<ClientModel>> getClients({String? query});
  Future<bool> approveClientKyc(int clientId);
  Future<List<TransactionModel>> getAccountTransactions(int accountId);
  Future<List<TransactionModel>> getOperations({
    String? search,
    DateTime? dateStart,
    DateTime? dateEnd,
    DateTime? date,
    int? sessionId,
  });
  Future<AccountModel?> searchAccount(String query);
  Future<TransactionModel> processDeposit({
    required int accountId,
    required double amount,
    String? note,
  });
  Future<TransactionModel> processWithdrawal({
    required int accountId,
    required double amount,
    String? note,
  });
  Future<TransactionModel> processTransfer({
    required int sourceAccountId,
    required int destinationAccountId,
    required double amount,
    String? note,
  });
  Future<ClientModel> registerExpressClient({
    required String firstName,
    required String lastName,
    required String phone,
    String? email,
    String? address,
    String? gender,
    String? idType,
    String? idNumber,
    double initialTontineAmount = 1000,
    bool createTontine = false,
  });
  Future<AccountModel> createAccount({
    required int clientId,
    required String accountType,
    double? targetAmount,
    int? cycleDurationMonths,
    String? paymentFrequency,
  });
  Future<ClientModel> updateClient({
    required int clientId,
    required String firstName,
    required String lastName,
    required String phone,
    String? email,
    String? address,
    String? profession,
  });

  // Loans & Credits Lifecycle
  Future<({List<LoanModel> loans, LoanMetricsModel metrics})> getLoans({String? status, String? search});
  Future<Map<String, dynamic>> getLoanConfig();
  Future<ClientEligibilityModel> getClientLoanEligibility(int clientId);
  Future<Map<String, dynamic>> simulateLoan({required double amount, required int durationMonths, double? interestRate});
  Future<LoanModel> applyLoan({
    required int clientId,
    required double requestedAmount,
    required int durationMonths,
    String? purpose,
    String? collateralDescription,
    double? interestRate,
  });
  Future<LoanModel> approveLoan({required int loanId, double? approvedAmount, String? notes});
  Future<Map<String, dynamic>> disburseLoan({required int loanId, String? paymentMethod, String? notes});
  Future<Map<String, dynamic>> repayLoan({required int loanId, required double amount, String? paymentMethod, String? notes});
  Future<List<LoanPaymentModel>> getLoanSchedule(int loanId);

  // Vault Transfer (Approvisionnement / Décharge) & Transaction Reversals (Void)
  Future<Map<String, dynamic>> vaultTransfer({
    required String type,
    required double amount,
    String? notes,
  });
  Future<Map<String, dynamic>> reverseTransaction({
    required int transactionId,
    required String supervisorPin,
    required String reason,
  });

  // Offline Sync
  Future<Map<String, dynamic>> syncTransactions(List<Map<String, dynamic>> transactions);

  // Field Team Supervision & Daily Collections
  Future<AgencyFieldTeamResponse> getFieldAgents({
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    String? period,
    String? search,
  });
  Future<FieldAgentDetailResponse> getFieldAgentDetails(
    int agentId, {
    DateTime? date,
    DateTime? dateStart,
    DateTime? dateEnd,
    String? period,
  });
}
