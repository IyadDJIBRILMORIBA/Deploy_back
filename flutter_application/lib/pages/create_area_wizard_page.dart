import 'package:flutter/material.dart';
import '../models/trigger_action_model.dart';
import '../services/create_area_data.dart';
import '../services/areas_data.dart';

class CreateAreaWizardPage extends StatefulWidget {
  const CreateAreaWizardPage({super.key});

  @override
  State<CreateAreaWizardPage> createState() => _CreateAreaWizardPageState();
}

class _CreateAreaWizardPageState extends State<CreateAreaWizardPage> {
  int step = 1;
  bool loading = true;
  
  List<TriggerActionModel> triggers = [];
  List<TriggerActionModel> actions = [];
  
  TriggerActionModel? selectedTrigger;
  TriggerActionModel? selectedAction;
  
  Map<String, dynamic> triggerConfig = {};
  Map<String, dynamic> actionConfig = {};
  
  final TextEditingController nameController = TextEditingController();

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  @override
  void dispose() {
    nameController.dispose();
    super.dispose();
  }

  Future<void> _loadData() async {
    setState(() => loading = true);
    try {
      final results = await Future.wait([
        CreateAreaData.getAvailableTriggers(),
        CreateAreaData.getAvailableActions(),
      ]);
      setState(() {
        triggers = results[0];
        actions = results[1];
        loading = false;
      });
    } catch (e) {
      debugPrint('Error loading data: $e');
      setState(() => loading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading data: $e')),
        );
      }
    }
  }

  void _selectTrigger(TriggerActionModel trigger) {
    setState(() {
      selectedTrigger = trigger;
      triggerConfig = {};
      step = 2;
    });
  }

  void _selectAction(TriggerActionModel action) {
    setState(() {
      selectedAction = action;
      actionConfig = {};
      step = 4;
    });
  }

  Future<void> _createArea() async {
    if (nameController.text.isEmpty || selectedTrigger == null || selectedAction == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please fill all required fields')),
      );
      return;
    }

    try {
      debugPrint('Creating AREA with:');
      debugPrint('  name: ${nameController.text}');
      debugPrint('  triggerService: ${selectedTrigger!.serviceId}');
      debugPrint('  triggerAction: ${selectedTrigger!.id}');
      debugPrint('  triggerParams: $triggerConfig');
      debugPrint('  actionService: ${selectedAction!.serviceId}');
      debugPrint('  actionReaction: ${selectedAction!.id}');
      debugPrint('  actionParams: $actionConfig');
      
      await AreasData.createArea(
        name: nameController.text,
        triggerService: selectedTrigger!.serviceId,
        triggerAction: selectedTrigger!.id,
        triggerParams: triggerConfig,
        actionService: selectedAction!.serviceId,
        actionReaction: selectedAction!.id,
        actionParams: actionConfig,
      );

      if (!mounted) return;

      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('✓ AREA created successfully!'),
          backgroundColor: Color(0xFF10B981),
        ),
      );

      Navigator.pop(context);
    } catch (e) {
      debugPrint('❌ Error creating area: $e');
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Error: $e'),
          backgroundColor: Color(0xFFEF4444),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF3F4F6),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.black87),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'Create New Automation',
          style: TextStyle(
            color: Colors.black87,
            fontSize: 20,
            fontWeight: FontWeight.bold,
          ),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(80),
          child: _buildProgressHeader(),
        ),
      ),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : _buildStepContent(),
    );
  }

  Widget _buildProgressHeader() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(
          bottom: BorderSide(color: Color(0xFFE5E7EB), width: 1),
        ),
      ),
      child: Column(
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              for (int i = 1; i <= 4; i++) ...[
                _buildStepIndicator(i),
                if (i < 4)
                  Container(
                    width: 40,
                    height: 2,
                    color: step > i ? Colors.green : const Color(0xFFE5E7EB),
                    margin: const EdgeInsets.symmetric(horizontal: 8),
                  ),
              ],
            ],
          ),
          const SizedBox(height: 12),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _buildStepLabel('Choose Trigger', step >= 1),
                const Text(' → ', style: TextStyle(color: Color(0xFFD1D5DB))),
                _buildStepLabel('Configure', step >= 2),
                const Text(' → ', style: TextStyle(color: Color(0xFFD1D5DB))),
                _buildStepLabel('Choose Action', step >= 3),
                const Text(' → ', style: TextStyle(color: Color(0xFFD1D5DB))),
                _buildStepLabel('Review', step >= 4),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStepIndicator(int stepNumber) {
    final isActive = step >= stepNumber;
    final isCompleted = step > stepNumber;
    
    return Container(
      width: 40,
      height: 40,
      decoration: BoxDecoration(
        color: isCompleted
            ? Colors.green
            : isActive
                ? const Color(0xFF2563EB)
                : const Color(0xFFE5E7EB),
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Text(
          isCompleted ? '✓' : stepNumber.toString(),
          style: TextStyle(
            color: isActive || isCompleted ? Colors.white : const Color(0xFF9CA3AF),
            fontWeight: FontWeight.bold,
            fontSize: 16,
          ),
        ),
      ),
    );
  }

  Widget _buildStepLabel(String label, bool isActive) {
    return Text(
      label,
      style: TextStyle(
        fontSize: 13,
        fontWeight: isActive ? FontWeight.w600 : FontWeight.normal,
        color: isActive ? const Color(0xFF2563EB) : const Color(0xFF9CA3AF),
      ),
    );
  }

  Widget _buildStepContent() {
    switch (step) {
      case 1:
        return _buildSelectTriggerStep();
      case 2:
        return _buildConfigureTriggerStep();
      case 3:
        return _buildSelectActionStep();
      case 4:
        return _buildReviewStep();
      default:
        return const SizedBox();
    }
  }

  Widget _buildSelectTriggerStep() {
    // Regrouper les triggers par service
    final Map<String, List<TriggerActionModel>> groupedTriggers = {};
    for (var trigger in triggers) {
      if (!groupedTriggers.containsKey(trigger.serviceId)) {
        groupedTriggers[trigger.serviceId] = [];
      }
      groupedTriggers[trigger.serviceId]!.add(trigger);
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'When this happens...',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 24),
          ...groupedTriggers.entries.map((entry) {
            return Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE5E7EB)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        entry.value.first.icon,
                        size: 28,
                        color: const Color(0xFF2563EB),
                      ),
                      const SizedBox(width: 12),
                      Text(
                        entry.key.toUpperCase(),
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  ...entry.value.map((trigger) {
                    return InkWell(
                      onTap: () => _selectTrigger(trigger),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          border: Border.all(color: const Color(0xFFE5E7EB)),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              trigger.name,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.black87,
                              ),
                            ),
                            if (trigger.description.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                trigger.description,
                                style: const TextStyle(
                                  fontSize: 14,
                                  color: Color(0xFF6B7280),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    );
                  }).toList(),
                ],
              ),
            );
          }).toList(),
        ],
      ),
    );
  }

  Widget _buildConfigureTriggerStep() {
    if (selectedTrigger == null) return const SizedBox();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE5E7EB)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(
                      selectedTrigger!.icon,
                      size: 28,
                      color: const Color(0xFF2563EB),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            selectedTrigger!.name,
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                          ),
                          if (selectedTrigger!.description.isNotEmpty)
                            Text(
                              selectedTrigger!.description,
                              style: const TextStyle(
                                fontSize: 14,
                                color: Color(0xFF6B7280),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                _buildConfigFields(
                  selectedTrigger!.id,
                  selectedTrigger!.serviceId,
                  triggerConfig,
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => setState(() => step = 1),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    side: const BorderSide(color: Color(0xFFE5E7EB)),
                  ),
                  child: const Text('← Back'),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: () => setState(() => step = 3),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    elevation: 0,
                  ),
                  child: const Text('Next: Choose Action →'),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildSelectActionStep() {
    // Regrouper les actions par service
    final Map<String, List<TriggerActionModel>> groupedActions = {};
    for (var action in actions) {
      if (!groupedActions.containsKey(action.serviceId)) {
        groupedActions[action.serviceId] = [];
      }
      groupedActions[action.serviceId]!.add(action);
    }

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              OutlinedButton(
                onPressed: () => setState(() => step = 2),
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  side: const BorderSide(color: Color(0xFFE5E7EB)),
                ),
                child: const Text('← Back'),
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Text(
            'Then do this...',
            style: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: Colors.black87,
            ),
          ),
          const SizedBox(height: 24),
          ...groupedActions.entries.map((entry) {
            return Container(
              margin: const EdgeInsets.only(bottom: 16),
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE5E7EB)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 10,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(
                        entry.value.first.icon,
                        size: 28,
                        color: const Color(0xFF059669),
                      ),
                      const SizedBox(width: 12),
                      Text(
                        entry.key.toUpperCase(),
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          color: Colors.black87,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 16),
                  ...entry.value.map((action) {
                    return InkWell(
                      onTap: () => _selectAction(action),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 8),
                        padding: const EdgeInsets.all(16),
                        decoration: BoxDecoration(
                          border: Border.all(color: const Color(0xFFE5E7EB)),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              action.name,
                              style: const TextStyle(
                                fontSize: 16,
                                fontWeight: FontWeight.w600,
                                color: Colors.black87,
                              ),
                            ),
                            if (action.description.isNotEmpty) ...[
                              const SizedBox(height: 4),
                              Text(
                                action.description,
                                style: const TextStyle(
                                  fontSize: 14,
                                  color: Color(0xFF6B7280),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                    );
                  }).toList(),
                ],
              ),
            );
          }).toList(),
        ],
      ),
    );
  }

  Widget _buildReviewStep() {
    if (selectedAction == null || selectedTrigger == null) return const SizedBox();

    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Configure Action
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE5E7EB)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Icon(
                      selectedAction!.icon,
                      size: 28,
                      color: const Color(0xFF059669),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            selectedAction!.name,
                            style: const TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                              color: Colors.black87,
                            ),
                          ),
                          if (selectedAction!.description.isNotEmpty)
                            Text(
                              selectedAction!.description,
                              style: const TextStyle(
                                fontSize: 14,
                                color: Color(0xFF6B7280),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 24),
                _buildConfigFields(
                  selectedAction!.id,
                  selectedAction!.serviceId,
                  actionConfig,
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Name your automation
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE5E7EB)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Name your automation',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.black87,
                  ),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: nameController,
                  decoration: InputDecoration(
                    hintText: '${selectedTrigger!.serviceName} → ${selectedAction!.serviceName}',
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE5E7EB)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE5E7EB)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF2563EB), width: 2),
                    ),
                    contentPadding: const EdgeInsets.all(16),
                  ),
                ),
                const SizedBox(height: 24),

                // Summary
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFBFDBFE)),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Summary',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF1E40AF),
                        ),
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Icon(selectedTrigger!.icon, size: 24),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              selectedTrigger!.name,
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                          const Text(
                            '→',
                            style: TextStyle(
                              fontSize: 20,
                              color: Color(0xFF2563EB),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Icon(selectedAction!.icon, size: 24),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Text(
                              selectedAction!.name,
                              style: const TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Action buttons
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: () => setState(() => step = 3),
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    side: const BorderSide(color: Color(0xFFE5E7EB)),
                  ),
                  child: const Text('← Back'),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                flex: 2,
                child: ElevatedButton(
                  onPressed: _createArea,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF059669),
                    padding: const EdgeInsets.symmetric(vertical: 16),
                    elevation: 0,
                  ),
                  child: const Text(
                    '✓ Create Automation',
                    style: TextStyle(fontWeight: FontWeight.bold),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildConfigFields(String itemId, String serviceId, Map<String, dynamic> config) {
    // TODO: Récupérer le config_schema depuis l'API
    // Pour l'instant, utilisons des champs génériques basés sur le service
    
    if (serviceId == 'google' && itemId == 'new_email') {
      return Column(
        children: [
          _buildTextField(
            label: 'From (email)',
            hint: 'sender@example.com',
            required: false,
            onChanged: (value) => config['from'] = value,
            initialValue: config['from'] ?? '',
          ),
          const SizedBox(height: 16),
          _buildTextField(
            label: 'Subject contains',
            hint: 'invoice',
            required: false,
            onChanged: (value) => config['subject_contains'] = value,
            initialValue: config['subject_contains'] ?? '',
          ),
        ],
      );
    }
    
    if (serviceId == 'google' && itemId == 'send_email') {
      return Column(
        children: [
          _buildTextField(
            label: 'To',
            hint: 'recipient@example.com',
            required: true,
            onChanged: (value) => config['to'] = value,
            initialValue: config['to'] ?? '',
          ),
          const SizedBox(height: 16),
          _buildTextField(
            label: 'Subject',
            hint: 'Email subject',
            required: true,
            onChanged: (value) => config['subject'] = value,
            initialValue: config['subject'] ?? '',
          ),
          const SizedBox(height: 16),
          _buildTextField(
            label: 'Message',
            hint: 'Email body',
            required: true,
            maxLines: 4,
            onChanged: (value) => config['body'] = value,
            initialValue: config['body'] ?? '',
          ),
        ],
      );
    }
    
    if (serviceId == 'timer') {
      return const Text(
        'No configuration needed for this trigger.',
        style: TextStyle(
          color: Color(0xFF6B7280),
          fontSize: 14,
        ),
        textAlign: TextAlign.center,
      );
    }
    
    return const Text(
      'No configuration needed.',
      style: TextStyle(
        color: Color(0xFF6B7280),
        fontSize: 14,
      ),
      textAlign: TextAlign.center,
    );
  }

  Widget _buildTextField({
    required String label,
    required String hint,
    required bool required,
    required Function(String) onChanged,
    required String initialValue,
    int maxLines = 1,
  }) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        RichText(
          text: TextSpan(
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
              color: Color(0xFF374151),
            ),
            children: [
              TextSpan(text: label),
              if (required)
                const TextSpan(
                  text: ' *',
                  style: TextStyle(color: Color(0xFFEF4444)),
                ),
            ],
          ),
        ),
        const SizedBox(height: 8),
        TextField(
          onChanged: onChanged,
          maxLines: maxLines,
          controller: TextEditingController(text: initialValue)
            ..selection = TextSelection.collapsed(offset: initialValue.length),
          decoration: InputDecoration(
            hintText: hint,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFFD1D5DB)),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFFD1D5DB)),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(12),
              borderSide: const BorderSide(color: Color(0xFF2563EB), width: 2),
            ),
            contentPadding: const EdgeInsets.all(16),
          ),
        ),
      ],
    );
  }
}
