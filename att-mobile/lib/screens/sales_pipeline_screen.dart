import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/sales_provider.dart';

class SalesPipelineScreen extends StatefulWidget {
  const SalesPipelineScreen({Key? key}) : super(key: key);

  @override
  _SalesPipelineScreenState createState() => _SalesPipelineScreenState();
}

class _SalesPipelineScreenState extends State<SalesPipelineScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      Provider.of<SalesProvider>(context, listen: false).fetchSalesPipelines();
    });
  }

  Color _getStageColor(String stage) {
    switch (stage) {
      case 'prospecting':
        return Colors.blue;
      case 'negotiation':
        return Colors.orange;
      case 'closed_won':
        return Colors.green;
      case 'closed_lost':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatStage(String stage) {
    switch (stage) {
      case 'prospecting':
        return 'Prospecting';
      case 'negotiation':
        return 'Negotiation';
      case 'closed_won':
        return 'Closed Won';
      case 'closed_lost':
        return 'Closed Lost';
      default:
        return stage;
    }
  }

  void _showUpdateDialog(Map<String, dynamic> pipeline) {
    String selectedStage = pipeline['stage'];
    final _notesController = TextEditingController(text: pipeline['notes'] ?? '');

    showDialog(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return AlertDialog(
              title: const Text('Update Pipeline'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('Client: ${pipeline['sales_report']['title']}'),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      value: selectedStage,
                      decoration: const InputDecoration(
                        labelText: 'Stage',
                        border: OutlineInputBorder(),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'prospecting', child: Text('Prospecting')),
                        DropdownMenuItem(value: 'negotiation', child: Text('Negotiation')),
                        DropdownMenuItem(value: 'closed_won', child: Text('Closed Won')),
                        DropdownMenuItem(value: 'closed_lost', child: Text('Closed Lost')),
                      ],
                      onChanged: (value) {
                        if (value != null) {
                          setState(() {
                            selectedStage = value;
                          });
                        }
                      },
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _notesController,
                      decoration: const InputDecoration(
                        labelText: 'Notes',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 3,
                    ),
                  ],
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('Cancel'),
                ),
                ElevatedButton(
                  onPressed: () async {
                    Navigator.pop(context);
                    final provider = Provider.of<SalesProvider>(context, listen: false);
                    final result = await provider.updateSalesPipeline(
                      pipeline['id'],
                      {
                        'stage': selectedStage,
                        'notes': _notesController.text,
                      },
                    );
                    
                    if (!mounted) return;
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text(result['message']),
                        backgroundColor: result['success'] ? Colors.green : Colors.red,
                      ),
                    );
                  },
                  child: const Text('Update'),
                ),
              ],
            );
          },
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Sales Pipeline'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              Provider.of<SalesProvider>(context, listen: false).fetchSalesPipelines();
            },
          ),
        ],
      ),
      body: Consumer<SalesProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.salesPipelines.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.salesPipelines.isEmpty) {
            return const Center(
              child: Text('No active pipelines found.'),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(8),
            itemCount: provider.salesPipelines.length,
            itemBuilder: (context, index) {
              final pipeline = provider.salesPipelines[index];
              final report = pipeline['sales_report'];
              
              return Card(
                elevation: 2,
                margin: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              report?['title'] ?? 'Unknown Client',
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: _getStageColor(pipeline['stage']).withOpacity(0.1),
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(color: _getStageColor(pipeline['stage'])),
                            ),
                            child: Text(
                              _formatStage(pipeline['stage']),
                              style: TextStyle(
                                color: _getStageColor(pipeline['stage']),
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Expected Revenue', style: TextStyle(color: Colors.grey, fontSize: 12)),
                              Text(
                                'Rp ${double.parse(pipeline['expected_revenue'].toString()).toStringAsFixed(0)}',
                                style: const TextStyle(fontWeight: FontWeight.w500),
                              ),
                            ],
                          ),
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              const Text('Probability', style: TextStyle(color: Colors.grey, fontSize: 12)),
                              Text(
                                '${pipeline['probability']}%',
                                style: const TextStyle(fontWeight: FontWeight.w500),
                              ),
                            ],
                          ),
                        ],
                      ),
                      if (pipeline['notes'] != null && pipeline['notes'].toString().isNotEmpty) ...[
                        const SizedBox(height: 12),
                        const Text('Notes', style: TextStyle(color: Colors.grey, fontSize: 12)),
                        Text(pipeline['notes']),
                      ],
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: OutlinedButton.icon(
                          icon: const Icon(Icons.edit, size: 18),
                          label: const Text('Update Stage'),
                          onPressed: () => _showUpdateDialog(pipeline),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            },
          );
        },
      ),
    );
  }
}
